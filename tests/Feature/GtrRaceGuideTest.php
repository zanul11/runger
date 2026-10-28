<?php

namespace Tests\Feature;

use App\Models\GtrCategory;
use App\Models\GtrSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GtrRaceGuideTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create(['name' => 'Admin', 'email' => 'a@t.id', 'password' => 'secret', 'role' => User::ROLE_ADMIN]);
    }

    private function seedContent(): void
    {
        GtrSetting::current()->update([
            'title' => 'Gerung Trail Run 2026',
            'date_text' => 'Minggu, 29 November 2026',
            'rpc_requirements' => "- Email konfirmasi\n- KTP asli",
            'rpc_proxy_note' => 'Diwakilkan dengan surat kuasa.',
            'venue_name' => 'Kantor Desa Banyu Urip',
            'venue_maps_url' => 'https://maps.app.goo.gl/abc',
            'venue_directions' => "1. Buka Google Maps\n2. Tuju Kantor Desa",
            'parking_name' => 'Lapangan Desa',
            'parking_notes' => 'Ikuti arahan petugas.',
        ]);
        GtrCategory::create([
            'name' => '15K', 'slug' => 'gtr-15k', 'distance' => '15 K', 'is_active' => true,
            'mandatory_gear' => ['Vest', 'Softflask 500 ml'],
            'water_stations' => [
                ['type' => 'cp', 'km' => 5.6, 'name' => 'Puncak Keteri', 'location' => 'Bukit Keteri', 'facilities' => ['Air mineral'], 'cutoff' => '08:00', 'notes' => 'Wajib scan BIB.'],
            ],
        ]);
    }

    public function test_generator_renders_new_slides_for_admin(): void
    {
        $this->seedContent();

        $this->actingAs($this->admin())->get(route('gtr.race-guide'))
            ->assertOk()
            ->assertSee('RPC Requirements')
            ->assertSee('Kantor Desa Banyu Urip')
            ->assertSee('Lapangan Desa')
            ->assertSee('Puncak Keteri')
            ->assertSee('Wajib scan BIB.')
            ->assertSee('Publikasikan ke website');
    }

    public function test_marshal_cannot_publish(): void
    {
        $marshal = User::create(['name' => 'M', 'email' => 'm@t.id', 'password' => 'x', 'role' => User::ROLE_MARSHAL]);

        $this->actingAs($marshal)->get(route('gtr.race-guide'))->assertForbidden();
        $this->actingAs($marshal)->postJson(route('gtr.race-guide.unpublish'))->assertForbidden();
    }

    public function test_publish_flow_shows_menu_page_and_zip(): void
    {
        Storage::fake('public');
        $this->seedContent();
        $admin = $this->admin();

        // Sebelum publikasi: menu tersembunyi, halaman "segera hadir".
        $this->get(route('gtr.guide'))->assertOk()->assertSee('Segera Hadir');
        $this->get(route('gtr.rules'))->assertDontSee(route('gtr.guide'));
        $this->get(route('gtr.guide.zip'))->assertNotFound();
        $this->get(route('gtr.guide.pdf'))->assertNotFound();

        $paths = [];
        foreach (['cover', 'rpc-requirements'] as $i => $name) {
            $paths[] = $this->actingAs($admin)->post(route('gtr.race-guide.upload'), [
                'batch' => 'batchabc123', 'index' => $i, 'name' => $name,
                'file' => UploadedFile::fake()->image($name . '.jpg', 1080, 1350),
            ])->assertOk()->json('path');
        }
        $this->assertSame('gtr/race-guide/batchabc123/01-cover.jpg', $paths[0]);

        $this->actingAs($admin)->postJson(route('gtr.race-guide.publish'), ['batch' => 'batchabc123', 'files' => $paths])
            ->assertOk()->assertJson(['ok' => true, 'count' => 2]);

        $this->assertTrue(GtrSetting::current()->raceGuidePublished());
        Storage::disk('public')->assertExists('gtr/race-guide/batchabc123/gtr-race-guide.zip');

        Storage::disk('public')->assertExists('gtr/race-guide/batchabc123/gtr-race-guide.pdf');
        $pdf = Storage::disk('public')->get('gtr/race-guide/batchabc123/gtr-race-guide.pdf');
        $this->assertStringStartsWith('%PDF-1.4', $pdf);
        $this->assertSame(2, substr_count($pdf, '/Type /Page '));

        $this->get(route('gtr.guide'))->assertOk()
            ->assertSee('01-cover.jpg')
            ->assertSee('Unduh PDF')
            ->assertSee('data-src="/storage/gtr/race-guide/batchabc123/gtr-race-guide.pdf', false);
        $this->get(route('gtr.guide.pdf'))->assertOk()->assertDownload('Race Guide - Gerung Trail Run.pdf');

        // Race guide lama tanpa PDF: PDF dibuat otomatis dari slide yang tersimpan.
        Storage::disk('public')->delete('gtr/race-guide/batchabc123/gtr-race-guide.pdf');
        $this->get(route('gtr.guide.pdf'))->assertOk();
        Storage::disk('public')->assertExists('gtr/race-guide/batchabc123/gtr-race-guide.pdf');
        $this->get(route('gtr.rules'))->assertSee(route('gtr.guide'));
        $this->get(route('gtr.guide.zip'))->assertOk()->assertDownload('Race Guide - Gerung Trail Run.zip');

        // Path di luar batch ditolak.
        $this->actingAs($admin)->postJson(route('gtr.race-guide.publish'), ['batch' => 'batchabc123', 'files' => ['gtr/header/x.jpg']])
            ->assertStatus(422);

        $this->actingAs($admin)->postJson(route('gtr.race-guide.unpublish'))->assertOk();
        $this->assertFalse(GtrSetting::current()->fresh()->raceGuidePublished());
        Storage::disk('public')->assertMissing('gtr/race-guide/batchabc123/01-cover.jpg');
        $this->get(route('gtr.guide'))->assertSee('Segera Hadir');
    }
}
