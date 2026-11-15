<?php

namespace Tests\Feature;

use App\Models\GtrPrepItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Aplikasi panitia GTR Crew: URL /crew (pengganti /scanner) & checklist persiapan panitia. */
class GtrCrewPrepTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $marshal;

    private User $panitia;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::create(['name' => 'Admin', 'username' => 'admin', 'password' => 'rahasia6', 'role' => User::ROLE_ADMIN]);
        $this->marshal = User::create(['name' => 'Lukman', 'username' => 'm1', 'password' => 'rahasia6', 'role' => User::ROLE_MARSHAL]);
        $this->panitia = User::create(['name' => 'Sinta', 'username' => 'sinta', 'password' => 'rahasia6', 'role' => User::ROLE_PANITIA]);
    }

    // ---------- URL /crew ----------

    public function test_crew_url_serves_app_and_old_scanner_url_redirects(): void
    {
        $this->get('/crew/app')->assertOk()->assertSee('GTR Crew')->assertSee('/crew/app.webmanifest', false);
        $this->get('/crew')->assertRedirect('/crew/app');

        $this->get('/scanner')->assertRedirect('/crew/app');
        $this->get('/scanner/app')->assertRedirect('/crew/app');
        $this->get('/scanner/apa/saja')->assertRedirect('/crew/app');
    }

    public function test_old_scanner_service_worker_moves_installed_apps_to_crew(): void
    {
        $res = $this->get('/scanner/sw.js')->assertOk()
            ->assertHeader('Service-Worker-Allowed', '/scanner/');
        $this->assertStringContainsString('application/javascript', $res->headers->get('Content-Type'));
        $js = $res->getContent();
        $this->assertStringContainsString('registration.unregister()', $js);
        $this->assertStringContainsString("navigate('/crew/app')", $js);
    }

    // ---------- checklist persiapan ----------

    public function test_admin_fills_template_once_and_manages_items(): void
    {
        Sanctum::actingAs($this->admin);
        $this->getJson('/api/timing/prep')->assertOk()->assertJsonPath('total', 0);

        $this->postJson('/api/timing/prep/template')->assertOk();
        $total = collect(GtrPrepItem::TEMPLATE)->flatten(1)->count();
        $res = $this->getJson('/api/timing/prep')->assertOk()->assertJsonPath('total', $total)->assertJsonPath('done', 0);
        $this->assertSame(array_keys(GtrPrepItem::TEMPLATE), $res->json('categories'));
        // Diulang: tidak menggandakan.
        $this->postJson('/api/timing/prep/template')->assertOk()->assertJsonPath('added', 0);
        $this->assertSame($total, GtrPrepItem::count());

        // Checklist lama tanpa kategori baru → hanya item yang belum ada yang ditambahkan; item lama utuh.
        $kept = GtrPrepItem::where('category', 'Rute & Marka')->first();
        $kept->forceFill(['pic' => 'Rudi', 'done_at' => now(), 'done_by' => $this->admin->id])->save();
        GtrPrepItem::where('category', 'Surat & Undangan')->delete();
        $this->postJson('/api/timing/prep/template')->assertOk()
            ->assertJsonPath('added', count(GtrPrepItem::TEMPLATE['Surat & Undangan']));
        $this->assertSame($total, GtrPrepItem::count());
        $this->assertSame('Rudi', $kept->fresh()->pic);
        $this->assertNotNull($kept->fresh()->done_at);
        $this->assertTrue(GtrPrepItem::where('title', 'Undangan Bupati Lombok Barat (pembukaan / flag off)')->where('phase', 'h7')->exists());

        $id = $this->postJson('/api/timing/prep', [
            'category' => 'Pos & Water Station', 'title' => 'Es batu', 'qty' => '10 kg', 'pic' => 'Rudi', 'location' => 'WS1', 'phase' => 'race',
        ])->assertCreated()->assertJsonPath('item.title', 'Es batu')->json('item.id');

        $this->putJson("/api/timing/prep/{$id}", ['category' => 'Pos & Water Station', 'title' => 'Es batu', 'qty' => '20 kg', 'phase' => 'h1'])
            ->assertOk()->assertJsonPath('item.qty', '20 kg')->assertJsonPath('item.pic', 'Rudi'); // field tak dikirim = tetap
        $this->putJson("/api/timing/prep/{$id}", ['category' => 'X', 'title' => 'Y', 'phase' => 'besok'])->assertStatus(422);

        $this->deleteJson("/api/timing/prep/{$id}")->assertOk();
        $this->assertNull(GtrPrepItem::find($id));
    }

    public function test_only_panitia_and_admin_can_edit_others_read_only(): void
    {
        $item = GtrPrepItem::create(['category' => 'Medis & Keamanan', 'title' => 'Kotak P3K', 'qty' => '1 per pos']);

        // Marshal: lihat saja.
        Sanctum::actingAs($this->marshal);
        $this->getJson('/api/timing/prep')->assertOk()->assertJsonPath('total', 1);
        $this->postJson("/api/timing/prep/{$item->id}/check", ['done' => true])->assertForbidden();
        $this->postJson('/api/timing/prep', ['category' => 'A', 'title' => 'B'])->assertForbidden();
        $this->putJson("/api/timing/prep/{$item->id}", ['category' => 'A', 'title' => 'B'])->assertForbidden();
        $this->deleteJson("/api/timing/prep/{$item->id}")->assertForbidden();
        $this->postJson('/api/timing/prep/template')->assertForbidden();

        // Panitia: centang & kelola.
        Sanctum::actingAs($this->panitia);
        $this->postJson("/api/timing/prep/{$item->id}/check", ['done' => true])->assertOk()
            ->assertJsonPath('item.done', true)
            ->assertJsonPath('item.done_by', 'Sinta');
        $this->getJson('/api/timing/prep')->assertJsonPath('done', 1);
        $this->postJson('/api/timing/prep', ['category' => 'Medis & Keamanan', 'title' => 'Tandu'])->assertCreated();

        // Centang ulang tidak menimpa siapa / kapan pertama dicentang.
        Sanctum::actingAs($this->admin);
        $this->postJson("/api/timing/prep/{$item->id}/check", ['done' => true])->assertJsonPath('item.done_by', 'Sinta');
        $this->postJson("/api/timing/prep/{$item->id}/check", ['done' => false])->assertOk()
            ->assertJsonPath('item.done', false)->assertJsonPath('item.done_by', null);
    }

    // ---------- role panitia ----------

    public function test_panitia_logs_in_without_participant_data_and_cannot_scan(): void
    {
        $res = $this->postJson('/api/timing/session', ['username' => 'sinta', 'password' => 'rahasia6', 'device_id' => 'hp-sinta'])
            ->assertOk()
            ->assertJsonPath('user.role', 'panitia')
            ->assertJsonPath('timing_point', null)
            ->assertJsonPath('participants', [])
            ->assertJsonPath('points', []);
        $token = $res->json('token');

        $this->withToken($token)->getJson('/api/timing/prep')->assertOk();
        $this->withToken($token)->postJson('/api/timing/scans/batch', ['scans' => []])->assertForbidden();
        $this->withToken($token)->getJson('/api/timing/racepack/lookup?code=1')->assertForbidden();
        $this->withToken($token)->getJson('/api/timing/admin/race')->assertForbidden();
        $this->assertFalse($this->panitia->canAccessPanel(filament()->getPanel('admin')));
    }

    // ---------- halaman publik ----------

    public function test_public_page_is_read_only_without_login_and_not_indexed(): void
    {
        $done = GtrPrepItem::create(['category' => 'Pos & Water Station', 'title' => 'Air mineral', 'qty' => '20 dus', 'pic' => 'Rudi', 'location' => 'WS1', 'phase' => 'h1']);
        $done->forceFill(['done_at' => now(), 'done_by' => $this->panitia->id])->save();
        GtrPrepItem::create(['category' => 'Medis & Keamanan', 'title' => 'Tandu', 'phase' => 'race', 'notes' => 'Pinjam dari puskesmas']);

        $this->get('/crew/persiapan')->assertOk()
            ->assertSee('Persiapan Panitia')
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false)
            ->assertSeeText('1 / 2')
            ->assertSee('Air mineral')->assertSee('20 dus · WS1 · PIC Rudi · H-1')
            ->assertSee('✓ Sinta', false)
            ->assertSee('Pinjam dari puskesmas')
            ->assertSee('Pos &amp; Water Station', false)
            ->assertDontSee('<form', false);
    }

    public function test_public_page_when_empty(): void
    {
        $this->get('/gerung-trail-run/persiapan')->assertRedirect('/crew/persiapan');
        $this->get('/crew/app')->assertOk()->assertSee('/crew/app.webmanifest', false); // catch-all app tetap jalan

        $this->get('/crew/persiapan')->assertOk()->assertSee('Checklist persiapan belum diisi.');
    }

    // ---------- admin web: menu Marshal & Panitia ----------

    public function test_admin_creates_panitia_without_post_and_converts_marshal(): void
    {
        $this->actingAs($this->admin);

        \Livewire\Livewire::test(\App\Filament\Resources\Marshals\Pages\CreateMarshal::class)
            ->fillForm(['name' => 'Dewi', 'username' => 'dewi', 'password' => 'rahasia6', 'role' => User::ROLE_PANITIA])
            ->call('create')
            ->assertHasNoFormErrors();
        $dewi = User::where('username', 'dewi')->sole();
        $this->assertSame(User::ROLE_PANITIA, $dewi->role);
        $this->assertSame(0, $dewi->timingPointAssignments()->count());

        // Marshal tanpa pos tidak bisa dibuat.
        \Livewire\Livewire::test(\App\Filament\Resources\Marshals\Pages\CreateMarshal::class)
            ->fillForm(['name' => 'Budi', 'username' => 'budi', 'password' => 'rahasia6', 'role' => User::ROLE_MARSHAL])
            ->call('create')
            ->assertHasFormErrors(['timing_point_id' => 'required']);

        // Marshal yang punya pos diubah jadi panitia → lepas dari pos.
        $event = \App\Models\Event::create(['slug' => 'gtr-2026', 'title' => 'GTR 2026', 'date' => '2026-11-29', 'time' => '06:00']);
        $tp = \App\Models\GtrTimingPoint::create(['code' => 'CP1', 'name' => 'Puncak', 'type' => 'checkpoint']);
        app(\App\Services\MarshalService::class)->assign($this->marshal, $event->id, $tp->id);
        \Livewire\Livewire::test(\App\Filament\Resources\Marshals\Pages\EditMarshal::class, ['record' => $this->marshal->getRouteKey()])
            ->fillForm(['role' => User::ROLE_PANITIA])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->assertSame(User::ROLE_PANITIA, $this->marshal->fresh()->role);
        $this->assertNull($this->marshal->fresh()->activeAssignment());

        // Daftar menampilkan marshal & panitia, bukan admin / racepack.
        $this->get('/admin/marshals')->assertOk()->assertSee('Dewi')->assertSee('Panitia');
    }

    public function test_prep_requires_login(): void
    {
        $this->getJson('/api/timing/prep')->assertUnauthorized();
    }
}
