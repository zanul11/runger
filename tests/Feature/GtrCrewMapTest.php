<?php

namespace Tests\Feature;

use App\Filament\Resources\CrewPoints\Pages\EditCrewPoints;
use App\Models\GtrCategory;
use App\Models\User;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Peta panitia /crew/peta: sign arah & marshal per kategori (khusus panitia, bukan Course Map peserta). */
class GtrCrewMapTest extends TestCase
{
    use RefreshDatabase;

    private function category(array $attrs = []): GtrCategory
    {
        return GtrCategory::create(array_merge([
            'name' => '15K',
            'slug' => '15k-' . uniqid(),
            'distance' => '15K',
            'is_active' => true,
            'route_points' => [[-8.68, 116.13], [-8.69, 116.14], [-8.70, 116.15]],
            'elevation_profile' => [[0, 50], [7.5, 120], [14.92, 60]],
        ], $attrs));
    }

    public function test_signs_and_marshals_are_normalized_and_sorted_by_km(): void
    {
        $cat = $this->category([
            'crew_signs' => [
                ['km' => '2.15', 'color' => 'red', 'direction' => 'left'],
                ['km' => 0.3, 'color' => 'blue', 'note' => ' Belok kiri '],
                ['km' => 12.7, 'color' => 'ungu'],          // warna tak dikenal → merah
                ['km' => '', 'color' => 'green'],            // tanpa KM → dibuang
            ],
            'crew_marshals' => [
                ['km' => 2, 'team' => 'Runger'],
                ['km' => 0.3, 'team' => 'Basecamp', 'count' => 2],
            ],
        ]);

        $signs = $cat->crewSigns();
        $this->assertSame([0.3, 2.15, 12.7], $signs->pluck('km')->all());
        $this->assertSame(['S1', 'S2', 'S3'], $signs->pluck('code')->all());
        $this->assertSame(['blue', 'red', 'red'], $signs->pluck('color')->all());
        $this->assertSame('Belok kiri', $signs[0]['note']);
        $this->assertSame('Biru', $signs[0]['color_label']);
        $this->assertNull($signs[0]['direction']);                // arah belum diisi
        $this->assertSame('Belok Kiri', $signs[1]['direction_label']);
        $this->assertSame(-90, $signs[1]['rotate']);

        $marshals = $cat->crewMarshals();
        $this->assertSame(['M1', 'M2'], $marshals->pluck('code')->all());
        $this->assertSame(['Basecamp', 'Runger'], $marshals->pluck('team')->all());
        $this->assertSame([2, 1], $marshals->pluck('count')->all());
    }

    public function test_crew_map_page_is_public_noindex_and_carries_points(): void
    {
        $this->category([
            'crew_signs' => [['km' => 13.8, 'color' => 'blue']],
            'crew_marshals' => [['km' => 6.3, 'team' => 'Runger']],
        ]);
        $this->category(['name' => '7K', 'distance' => '7K', 'route_points' => null]); // tanpa GPX → tidak tampil

        $res = $this->get('/crew/peta')->assertOk()
            ->assertSee('Peta Sign &amp; Marshal', false)
            ->assertSee('noindex', false)
            ->assertSee('"team":"Runger"', false)
            ->assertSee('"color":"blue"', false)
            ->assertSee('"km_total":14.92', false);

        $this->assertStringNotContainsString('"name":"7K"', $res->getContent());
    }

    public function test_crew_points_do_not_leak_into_public_course_map(): void
    {
        $cat = $this->category([
            'crew_signs' => [['km' => 2, 'color' => 'red', 'note' => 'RAHASIA-SIGN']],
            'crew_marshals' => [['km' => 2, 'team' => 'TIM-RAHASIA']],
        ]);

        $this->assertEmpty($cat->coursePois());
        $this->assertArrayNotHasKey('signs', $cat->courseSegments());
    }

    public function test_crew_map_without_routes_shows_empty_state(): void
    {
        $this->get('/crew/peta')->assertOk()->assertSee('Belum ada rute');
    }

    private function admin(): User
    {
        return User::create(['name' => 'Admin', 'email' => 'a@t.id', 'password' => 'secret', 'role' => User::ROLE_ADMIN]);
    }

    public function test_admin_sign_marshal_pages_render(): void
    {
        $cat = $this->category(['crew_signs' => [['km' => 2, 'color' => 'red']]]);
        $this->actingAs($this->admin());

        $this->get('/admin/sign-marshal')->assertOk()->assertSee('15K')->assertSee('1 belum ada arah');
        $this->get("/admin/sign-marshal/{$cat->id}/edit")->assertOk()->assertSee('Tambah Sign')->assertSee('Belok Kiri');
    }

    public function test_admin_edits_km_direction_and_color_and_saves_sorted_by_km(): void
    {
        $cat = $this->category();
        $this->actingAs($this->admin());

        Livewire::test(EditCrewPoints::class, ['record' => $cat->getRouteKey()])
            ->set('data.crew_signs', [
                'a' => ['km' => 5, 'direction' => 'right', 'color' => 'green', 'note' => ''],
                'b' => ['km' => 1.2, 'direction' => 'u_turn', 'color' => 'blue', 'note' => 'Putar di tugu'],
            ])
            ->set('data.crew_marshals', [
                'c' => ['km' => 3, 'team' => 'Runger', 'count' => 2, 'note' => ''],
            ])
            ->call('save')
            ->assertHasNoErrors();

        $signs = $cat->fresh()->crewSigns();
        $this->assertSame([1.2, 5.0], $signs->pluck('km')->all());
        $this->assertSame(['u_turn', 'right'], $signs->pluck('direction')->all());
        $this->assertSame(['blue', 'green'], $signs->pluck('color')->all());
        $this->assertSame(2, $cat->fresh()->crewMarshals()->first()['count']);
    }

    public function test_admin_rejects_km_beyond_route_length(): void
    {
        $cat = $this->category();
        $this->actingAs($this->admin());

        Livewire::test(EditCrewPoints::class, ['record' => $cat->getRouteKey()])
            ->set('data.crew_signs', ['a' => ['km' => 20, 'direction' => 'left', 'color' => 'red', 'note' => '']])
            ->call('save')
            ->assertHasErrors('data.crew_signs');
    }

    public function test_quick_save_from_map_persists_and_reports_validation_error(): void
    {
        $cat = $this->category(['crew_signs' => [['km' => 2, 'direction' => 'left', 'color' => 'red', 'note' => '']]]);
        $this->actingAs($this->admin());

        // geser / edit lewat peta: data diubah lalu quickSave() tanpa tombol Simpan
        Livewire::test(EditCrewPoints::class, ['record' => $cat->getRouteKey()])
            ->set('data.crew_signs', ['x' => ['km' => 2.37, 'direction' => 'slight_right', 'color' => 'green', 'note' => 'Serong di pohon']])
            ->call('quickSave')
            ->assertReturned(null);

        $sign = $cat->fresh()->crewSigns()->first();
        $this->assertSame([2.37, 'slight_right', 'green'], [$sign['km'], $sign['direction'], $sign['color']]);

        Livewire::test(EditCrewPoints::class, ['record' => $cat->getRouteKey()])
            ->set('data.crew_marshals', ['y' => ['km' => 99, 'team' => 'Runger', 'count' => 1, 'note' => '']])
            ->call('quickSave')
            ->assertReturned(fn ($msg) => str_contains((string) $msg, 'melebihi panjang rute'));

        $this->assertCount(0, $cat->fresh()->crewMarshals());
    }
}
