<?php

namespace Tests\Feature;

use App\Filament\Resources\RosterStructures\Pages\EditRosterStructure;
use App\Filament\Resources\RosterStructures\RosterStructureResource;
use App\Models\Event;
use App\Models\RosterArea;
use App\Models\RosterAssignment;
use App\Models\RosterPosition;
use App\Models\User;
use App\Services\RosterService;
use Database\Seeders\RosterGtr2026Seeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Tests\TestCase;

/** Roster panitia hari H: GTR Crew → Roster, halaman publik /crew/roster, struktur di Filament. */
class GtrCrewRosterTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $panitia;

    private User $marshal;

    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::create(['name' => 'Admin', 'username' => 'admin', 'password' => 'rahasia6', 'role' => User::ROLE_ADMIN]);
        $this->panitia = User::create(['name' => 'Sinta', 'username' => 'sinta', 'password' => 'rahasia6', 'role' => User::ROLE_PANITIA]);
        $this->marshal = User::create(['name' => 'Lukman', 'username' => 'm1', 'password' => 'rahasia6', 'role' => User::ROLE_MARSHAL]);
        Event::create(['slug' => 'gerung-trail-run', 'title' => 'GERUNG TRAIL RUN', 'date' => '2026-11-29', 'time' => '06:00']);
        $this->seed(RosterGtr2026Seeder::class);
        $this->event = Event::rosterActive();
    }

    private function pos(string $nama): RosterPosition
    {
        return RosterPosition::where('nama', $nama)->firstOrFail();
    }

    public function test_seeder_builds_gtr_structure_once_on_existing_event(): void
    {
        $this->assertSame('gerung-trail-run', $this->event->slug);
        $this->assertSame('GERUNG TRAIL RUN', $this->event->title, 'nama event lama tidak diubah');
        $this->assertTrue($this->event->roster_aktif);
        $this->assertSame('SD Negeri 4 Banyu Urip', $this->event->lokasi_race_village);
        $this->assertSame(['race-village', 'lapangan'], $this->event->rosterAreas->pluck('slug')->all());
        $this->assertSame(33, RosterPosition::count());
        $this->assertSame(['2', '8,1', '12,8'], $this->pos('Marshal 3')->km);

        $this->seed(RosterGtr2026Seeder::class);
        $this->assertSame(33, RosterPosition::count());
    }

    public function test_everyone_in_crew_can_view_but_only_panitia_and_admin_fill(): void
    {
        $p = $this->pos('Race Director');

        Sanctum::actingAs($this->marshal);
        $this->getJson('/api/timing/roster')->assertOk()
            ->assertJsonPath('perms.fill', false)
            ->assertJsonPath('event.lokasi_race_village', 'SD Negeri 4 Banyu Urip')
            ->assertJsonCount(2, 'areas');
        $this->putJson("/api/timing/roster/positions/{$p->id}/slots/1", ['nama' => 'X'])->assertForbidden();

        Sanctum::actingAs($this->panitia);
        $this->getJson('/api/timing/roster')->assertJsonPath('perms.fill', true)->assertJsonPath('perms.structure', false);
        $this->putJson("/api/timing/roster/positions/{$p->id}/slots/1", ['nama' => 'Pak Budi', 'no_hp' => '081234567890'])
            ->assertOk()->assertJsonPath('slot.nama', 'Pak Budi')->assertJsonPath('slot.updated_by', 'Sinta');

        // Simpan per field: field lain tidak tersentuh. Terakhir menang.
        Sanctum::actingAs($this->admin);
        $this->putJson("/api/timing/roster/positions/{$p->id}/slots/1", ['nama' => 'Bu Rina'])->assertJsonPath('slot.no_hp', '081234567890')
            ->assertJsonPath('slot.updated_by', 'Admin');
        $this->assertSame(1, RosterAssignment::count());

        $this->putJson("/api/timing/roster/positions/{$p->id}/slots/2", ['nama' => 'Lebih'])->assertUnprocessable();
        $this->putJson("/api/timing/roster/positions/{$p->id}/slots/1", ['no_hp' => 'abc'])->assertUnprocessable();
    }

    public function test_public_page_is_read_only_and_masks_phones(): void
    {
        $p = $this->pos('Race Director');
        app(RosterService::class)->saveSlot($p, 1, ['nama' => 'Pak Budi', 'no_hp' => '081234567890'], $this->admin);

        $this->get('/crew/roster')->assertOk()->assertSee('noindex', false)->assertSee('Roster Panitia');
        $res = $this->getJson('/crew/roster/data')->assertOk()
            ->assertJsonPath('public', true)
            ->assertJsonPath('perms.fill', false)
            ->assertJsonPath('areas.0.groups.0.positions.0.slots.0.nama', 'Pak Budi')
            ->assertJsonPath('areas.0.groups.0.positions.0.slots.0.no_hp', '0812•••890')
            ->assertJsonPath('areas.0.groups.0.positions.0.slots.0.updated_by', null);
        $this->assertStringNotContainsString('081234567890', $res->getContent());
    }

    public function test_inactive_position_hidden_but_data_kept(): void
    {
        $p = $this->pos('Kebersihan');
        app(RosterService::class)->saveSlot($p, 1, ['nama' => 'Ani'], $this->admin);
        $p->update(['aktif' => false]);

        Sanctum::actingAs($this->admin);
        $names = collect($this->getJson('/api/timing/roster')->json('areas'))->flatMap(fn ($a) => collect($a['groups'])->flatMap(fn ($g) => collect($g['positions'])->pluck('nama')));
        $this->assertNotContains('Kebersihan', $names);
        $this->assertSame(32, $names->count());
        $this->assertSame('Ani', RosterAssignment::where('position_id', $p->id)->value('nama'));
        $this->putJson("/api/timing/roster/positions/{$p->id}/slots/1", ['nama' => 'B'])->assertUnprocessable();
    }

    public function test_lowering_max_is_blocked_while_filled_slots_above_then_compact_moves_them(): void
    {
        $svc = app(RosterService::class);
        $p = $this->pos('Start/Finish & Chute'); // 3–4
        $svc->saveSlot($p, 1, ['nama' => 'A'], $this->admin);
        $svc->saveSlot($p, 4, ['nama' => 'D'], $this->admin);
        $svc->saveSlot($p, 3, ['nama' => ''], $this->admin); // baris kosong

        $this->assertStringContainsString('Ada 1 petugas di slot yang akan dihapus', $svc->maxChangeError($p, 3));
        $this->assertNull($svc->maxChangeError($p, 4));

        $this->assertSame(['moved' => 1, 'left' => 0], $svc->compactSlots($p, 3));
        $this->assertNull($svc->maxChangeError($p, 3));
        $this->assertSame([1 => 'A', 2 => 'D'], RosterAssignment::where('position_id', $p->id)->filled()->pluck('nama', 'slot_no')->all());

        // Max dinaikkan → slot baru langsung tersedia.
        $p->update(['max_petugas' => 6]);
        Sanctum::actingAs($this->admin);
        $this->putJson("/api/timing/roster/positions/{$p->id}/slots/6", ['nama' => 'F'])->assertOk();
    }

    public function test_consumption_guest_count_saved_by_panitia(): void
    {
        Sanctum::actingAs($this->panitia);
        $this->putJson('/api/timing/roster/consumption', ['tamu_tambahan' => 25])->assertOk();
        $this->getJson('/api/timing/roster')->assertJsonPath('consumption.tamu_tambahan', 25)
            ->assertJsonPath('consumption.area_acuan_nama', 'Lapangan')
            ->assertJsonPath('consumption.air_per_orang', 2);
    }

    public function test_copy_structure_to_next_year_without_names(): void
    {
        app(RosterService::class)->saveSlot($this->pos('Race Director'), 1, ['nama' => 'Pak Budi'], $this->admin);
        $next = Event::create(['slug' => 'gtr-2027', 'title' => 'GTR 2027', 'date' => '2027-11-28', 'time' => '06:00']);

        $n = app(RosterService::class)->copyStructure($this->event, $next);
        $this->assertSame(33, $n);
        $this->assertSame(2, $next->rosterAreas()->count());
        $this->assertSame(1, RosterAssignment::count(), 'nama petugas tidak ikut disalin');
        $this->assertSame('lapangan', $next->rosterConsumption->areaAcuan->slug);
        $this->assertNotSame($this->event->rosterConsumption->area_acuan_id, $next->rosterConsumption->area_acuan_id);

        $this->expectException(ValidationException::class);
        app(RosterService::class)->copyStructure($this->event, $next);
    }

    public function test_export_then_import_assignments_with_preview(): void
    {
        $svc = app(RosterService::class);
        $svc->saveSlot($this->pos('Race Director'), 1, ['nama' => 'Pak Budi', 'no_hp' => '0811'], $this->admin);
        Sanctum::actingAs($this->panitia);
        $export = $this->getJson('/api/timing/roster/export')->assertOk()->assertJsonPath('format', 'gtr-roster')->json();

        RosterAssignment::query()->delete();
        $export['areas'][0]['groups'][0]['positions'][0]['id'] = 999999; // ID asing → dicocokkan dari nama

        $preview = $this->postJson('/api/timing/roster/import/preview', ['mode' => 'petugas', 'data' => $export])->assertOk()
            ->assertJsonPath('applied', false)->assertJsonPath('summary.tambah', 1)->json();
        $this->assertSame('Pak Budi (0811)', $preview['changes'][0]['to']);
        $this->assertSame(0, RosterAssignment::count(), 'preview tidak menyimpan');

        $this->postJson('/api/timing/roster/import', ['mode' => 'petugas', 'data' => $export])->assertOk()->assertJsonPath('applied', true);
        $this->assertSame('Pak Budi', RosterAssignment::first()->nama);
        $this->assertSame($this->panitia->id, RosterAssignment::first()->updated_by);

        $this->postJson('/api/timing/roster/import', ['mode' => 'struktur', 'data' => $export])->assertForbidden();
        $this->postJson('/api/timing/roster/import/preview', ['mode' => 'petugas', 'data' => ['x' => 1]])->assertUnprocessable();
    }

    public function test_structure_import_syncs_structure_for_admin(): void
    {
        Sanctum::actingAs($this->admin);
        $export = $this->getJson('/api/timing/roster/export')->json();
        // Hapus grup "Pendukung" dari Race Village, tambah Marshal 12, ubah max Marshal 1.
        array_pop($export['areas'][0]['groups']);
        $marshals = &$export['areas'][1]['groups'][1]['positions'];
        $marshals[0]['max_petugas'] = 2;
        $marshals[] = array_merge($marshals[10], ['id' => null, 'nama' => 'Marshal 12', 'km' => ['13'], 'slots' => [['slot_no' => 1, 'nama' => 'Wahab', 'no_hp' => '', 'catatan' => '']]]);
        unset($marshals);

        $res = $this->postJson('/api/timing/roster/import/preview', ['mode' => 'struktur', 'data' => $export])->assertOk()->json();
        $this->assertSame(33, RosterPosition::count());
        $this->assertSame(4, $res['summary']['hapus']); // 3 pos + 1 grup
        $this->assertGreaterThanOrEqual(2, $res['summary']['tambah']);

        $this->postJson('/api/timing/roster/import', ['mode' => 'struktur', 'data' => $export])->assertOk();
        $this->assertSame(33 - 3 + 1, RosterPosition::count());
        $this->assertSame(2, $this->pos('Marshal 1')->max_petugas);
        $this->assertSame('Wahab', $this->pos('Marshal 12')->assignments()->value('nama'));
        $this->assertSame(['race-village', 'lapangan'], RosterArea::orderBy('urutan')->pluck('slug')->all());
    }

    public function test_only_one_active_event(): void
    {
        $other = Event::create(['slug' => 'lain', 'title' => 'Lain', 'date' => '2027-01-01', 'time' => '06:00', 'roster_aktif' => true]);
        $this->assertFalse($this->event->fresh()->roster_aktif);
        $this->assertTrue(Event::rosterActive()->is($other));
    }

    public function test_admin_structure_pages_render(): void
    {
        $this->actingAs($this->admin)->get('/admin/struktur-roster')->assertOk()->assertSee('GERUNG TRAIL RUN');
        $this->actingAs($this->admin)->get("/admin/struktur-roster/{$this->event->id}/edit")->assertOk()
            ->assertSee('Race Village')->assertSee('Konsumsi Race Village');
        $this->actingAs($this->admin)->get("/admin/events/{$this->event->id}/edit")->assertOk()->assertSee('Roster Panitia Hari H');
    }

    public function test_next_name_for_duplicated_position(): void
    {
        $f = [RosterStructureResource::class, 'nextName'];
        $this->assertSame('Marshal 12', $f('Marshal 11', ['Marshal 10', 'Marshal 11']));
        $this->assertSame('Marshal 13', $f('Marshal 11', ['Marshal 11', 'Marshal 12']));
        $this->assertSame('MC (salinan)', $f('MC', ['MC']));
    }

    private function positionPath(RosterPosition $p): string
    {
        $g = $p->group;

        return "data.rosterAreas.record-{$g->area_id}.groups.record-{$g->id}.positions";
    }

    public function test_filament_save_blocks_lowering_max_over_filled_slot_and_allows_others(): void
    {
        $p = $this->pos('Marshal 6'); // 1–2
        app(RosterService::class)->saveSlot($p, 2, ['nama' => 'Dua'], $this->admin);
        $this->actingAs($this->admin);
        $path = $this->positionPath($p);

        Livewire::test(EditRosterStructure::class, ['record' => $this->event->id])
            ->set("{$path}.record-{$p->id}.max_petugas", 1)
            ->call('save')
            ->assertHasErrors(["{$path}.record-{$p->id}.max_petugas"]);
        $this->assertSame(2, $p->fresh()->max_petugas);

        Livewire::test(EditRosterStructure::class, ['record' => $this->event->id])
            ->set("{$path}.record-{$p->id}.max_petugas", 3)
            ->set("{$path}.record-{$p->id}.km", ['3,4', '9'])
            ->set("{$path}.record-{$p->id}.aktif", false)
            ->call('save')
            ->assertHasNoErrors();
        $p->refresh();
        $this->assertSame([3, ['3,4', '9'], false], [$p->max_petugas, $p->km, $p->aktif]);
        $this->assertSame(33, RosterPosition::count());
        $this->assertSame('Dua', $p->assignments()->value('nama'));
    }

    public function test_filament_duplicate_position_increments_name(): void
    {
        $p = $this->pos('Marshal 11');
        $this->actingAs($this->admin);
        $path = $this->positionPath($p);

        Livewire::test(EditRosterStructure::class, ['record' => $this->event->id])
            ->callAction(TestAction::make('clone')->schemaComponent(Str::after($path, 'data.'))->arguments(['item' => "record-{$p->id}"]))
            ->call('save')
            ->assertHasNoErrors();
        $dup = RosterPosition::where('nama', 'Marshal 12')->firstOrFail();
        $this->assertSame([$p->group_id, ['11,75'], 1, 2], [$dup->group_id, $dup->km, $dup->min_petugas, $dup->max_petugas]);
        $this->assertSame(0, $dup->assignments()->count());
    }

    public function test_admin_renames_area_group_position_from_crew_app(): void
    {
        $p = $this->pos('Marshal 3');
        $g = $p->group;
        $a = $g->area;

        Sanctum::actingAs($this->panitia);
        $this->patchJson("/api/timing/roster/groups/{$g->id}", ['nama' => 'X'])->assertForbidden();

        Sanctum::actingAs($this->admin);
        $this->patchJson("/api/timing/roster/groups/{$g->id}", ['nama' => '  Marshal Rute  '])->assertOk()->assertJsonPath('nama', 'Marshal Rute');
        $this->patchJson("/api/timing/roster/areas/{$a->id}", ['nama' => 'Lapangan / Rute'])->assertOk();
        $this->patchJson("/api/timing/roster/positions/{$p->id}", ['nama' => 'Marshal 3 (Simpang)'])->assertOk();
        $this->patchJson("/api/timing/roster/groups/{$g->id}", ['nama' => ''])->assertUnprocessable();
        $this->patchJson('/api/timing/roster/groups/999999', ['nama' => 'Y'])->assertNotFound();

        $this->assertSame('Marshal Rute', $g->fresh()->nama);
        $this->assertSame('lapangan', $a->fresh()->slug, 'slug area tetap (acuan konsumsi)');
        $this->getJson('/api/timing/roster')->assertJsonPath('areas.1.nama', 'Lapangan / Rute')
            ->assertJsonPath('areas.1.groups.1.nama', 'Marshal Rute')
            ->assertJsonPath('consumption.area_acuan_nama', 'Lapangan / Rute');
    }

    public function test_admin_edits_position_count_and_jobdesk_from_crew_app(): void
    {
        $svc = app(RosterService::class);
        $p = $this->pos('Marshal 6'); // 1–2
        $svc->saveSlot($p, 2, ['nama' => 'Dua'], $this->admin);
        $url = "/api/timing/roster/positions/{$p->id}";

        Sanctum::actingAs($this->panitia);
        $this->patchJson($url, ['max_petugas' => 3])->assertForbidden();

        Sanctum::actingAs($this->admin);
        // Tambah orang + jobdesk.
        $this->patchJson($url, ['min_petugas' => 2, 'max_petugas' => 4, 'deskripsi' => '  Jaga tanjakan, bawa peluit.  '])->assertOk();
        $p->refresh();
        $this->assertSame([2, 4, 'Jaga tanjakan, bawa peluit.'], [$p->min_petugas, $p->max_petugas, $p->deskripsi]);
        $this->getJson('/api/timing/roster')->assertOk();
        $this->putJson("{$url}/slots/4", ['nama' => 'Empat'])->assertOk();

        // Validasi.
        $this->patchJson($url, ['min_petugas' => 3, 'max_petugas' => 2])->assertUnprocessable()->assertJsonValidationErrors('max_petugas');
        $this->patchJson($url, ['max_petugas' => 0])->assertUnprocessable();

        // Kurangi ke 1: ada petugas di slot 2 & 4 → ditolak; slot kosong tidak cukup.
        $this->patchJson($url, ['min_petugas' => 1, 'max_petugas' => 1])->assertUnprocessable()->assertJsonPath('can_compact', false);
        // Kurangi ke 2: muat dipindah ke slot 1.
        $this->patchJson($url, ['max_petugas' => 2])->assertUnprocessable()->assertJsonPath('can_compact', true)
            ->assertJsonPath('message', 'Ada 1 petugas di slot yang akan dihapus (slot 4).');
        $this->assertSame(4, $p->fresh()->max_petugas);
        $this->patchJson($url, ['max_petugas' => 2, 'compact' => true])->assertOk();
        $this->assertSame(2, $p->fresh()->max_petugas);
        $this->assertSame([1 => 'Empat', 2 => 'Dua'], RosterAssignment::where('position_id', $p->id)->filled()->orderBy('slot_no')->pluck('nama', 'slot_no')->all());

        // Jobdesk dikosongkan → null.
        $this->patchJson($url, ['deskripsi' => ''])->assertOk();
        $this->assertNull($p->fresh()->deskripsi);
    }

    public function test_admin_deletes_position_group_area_from_crew_app(): void
    {
        $svc = app(RosterService::class);
        $p = $this->pos('Marshal 3');
        $g = $p->group;
        $a = $g->area;
        $svc->saveSlot($p, 1, ['nama' => 'Wahab'], $this->admin);

        Sanctum::actingAs($this->panitia);
        $this->deleteJson("/api/timing/roster/positions/{$p->id}")->assertForbidden();
        $this->assertNotNull($p->fresh());

        Sanctum::actingAs($this->admin);
        $this->deleteJson("/api/timing/roster/positions/{$p->id}")->assertOk()
            ->assertJsonPath('petugas', 1)
            ->assertJsonPath('message', 'Pos "Marshal 3" dihapus (1 petugas ikut terhapus).');
        $this->assertNull($p->fresh());
        $this->assertSame(0, RosterAssignment::where('position_id', $p->id)->count());
        $this->deleteJson("/api/timing/roster/positions/{$p->id}")->assertNotFound();

        // Kelompok: semua pos di dalamnya ikut terhapus.
        $ids = $g->positions()->pluck('id');
        $this->assertNotEmpty($ids);
        $this->deleteJson("/api/timing/roster/groups/{$g->id}")->assertOk()->assertJsonPath('positions', $ids->count());
        $this->assertSame(0, RosterPosition::whereIn('id', $ids)->count());

        // Area acuan konsumsi dihapus → acuan kosong, roster tetap bisa dimuat.
        $areas = $this->event->rosterAreas()->count();
        $this->deleteJson("/api/timing/roster/areas/{$a->id}")->assertOk();
        $this->assertNull(RosterArea::find($a->id));
        $this->getJson('/api/timing/roster')->assertOk()
            ->assertJsonCount($areas - 1, 'areas')
            ->assertJsonPath('consumption.area_acuan_id', null);
    }
}
