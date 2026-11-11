<?php

namespace Tests\Feature;

use App\Http\Controllers\GtrBibCheckController;
use App\Models\GtrCategory;
use App\Models\GtrRegistration;
use App\Models\Runner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Kiosk Cek BIB publik: tanpa login, hanya peserta lunas ber-BIB, tanpa data pribadi. */
class GtrBibCheckTest extends TestCase
{
    use RefreshDatabase;

    private function seedRunners(): void
    {
        $c7 = GtrCategory::create(['name' => '7K', 'slug' => 'gtr-7k', 'distance' => '7 KM', 'tag' => 'Fun Trail', 'color' => '#35E656', 'is_active' => true, 'sort_order' => 1,
            'elevation_profile' => [[0, 81], [0.06, 78.4], [0.12, 90]]]);
        $c15 = GtrCategory::create(['name' => '15K', 'slug' => 'gtr-15k', 'distance' => '15 KM', 'color' => '#E53935', 'is_active' => true, 'sort_order' => 2]);

        foreach ([[$c7, '7001', 'paid', 'Laki-laki'], [$c15, '5001', 'paid', 'Perempuan'], [$c15, null, 'pending', 'Perempuan']] as $i => [$c, $bib, $st, $g]) {
            $runner = Runner::create(['first_name' => 'Pelari'.$i, 'email' => "r{$i}@t.id", 'password' => 'x']);
            GtrRegistration::create([
                'runner_id' => $runner->id, 'gtr_category_id' => $c->id, 'full_name' => 'Peserta '.$i, 'gender' => $g,
                'bib_number' => $bib, 'payment_status' => $st, 'nik' => '520101010190000'.$i,
                'emergency_name' => 'Ibu', 'emergency_contact' => '6281234567890',
            ]);
        }
    }

    public function test_kiosk_page_is_public(): void
    {
        $this->get(route('gtr.bib-check'))->assertOk()->assertSee('Scan BIB kamu')->assertSee(json_encode(route('gtr.bib-check.data')), false);
    }

    public function test_data_lists_paid_runners_by_bib_without_private_fields(): void
    {
        $this->seedRunners();

        $c7 = GtrCategory::where('slug', 'gtr-7k')->first();
        $res = $this->getJson(route('gtr.bib-check.data'))->assertOk()
            ->assertJsonPath('count', 2)
            ->assertJsonPath('runners.7001.name', 'Peserta 0')
            ->assertJsonPath('runners.7001.category_id', $c7->id)
            ->assertJsonPath('runners.7001.gender', 'M')
            ->assertJsonPath('runners.5001.gender', 'F')
            ->assertJsonPath("categories.{$c7->id}.name", '7K')
            ->assertJsonPath("categories.{$c7->id}.tag", 'Fun Trail')
            ->assertJsonPath("categories.{$c7->id}.color", '#35E656')
            ->assertJsonPath("categories.{$c7->id}.ele", [81, 78, 90]);

        $this->assertSame(['bib', 'name', 'gender', 'category_id'], array_keys($res->json('runners.7001')));
        $body = $res->getContent();
        foreach (['5201010101900', '6281234567890', 'GTR2026', 'r0@t.id', 'Peserta 2'] as $secret) {
            $this->assertStringNotContainsString($secret, $body);
        }
    }

    public function test_cached_data_is_identical_to_fresh_data(): void
    {
        $this->seedRunners();

        $first = $this->getJson(route('gtr.bib-check.data'))->assertOk()->json();
        $second = $this->getJson(route('gtr.bib-check.data'))->assertOk()->json(); // dari cache

        unset($first['generated_at_ms'], $second['generated_at_ms']);
        $this->assertSame(2, $second['count']);
        $this->assertSame($first, $second);
    }

    public function test_data_is_an_object_even_when_empty(): void
    {
        $this->getJson(route('gtr.bib-check.data'))->assertOk()->assertJsonPath('count', 0);
        $this->assertStringContainsString('"runners":{}', $this->get(route('gtr.bib-check.data'))->getContent());
    }

    public function test_service_worker_covers_kiosk_scope_and_precaches_local_assets(): void
    {
        $res = $this->get(route('gtr.bib-check.sw'))->assertOk()
            ->assertHeader('Service-Worker-Allowed', GtrBibCheckController::SCOPE)
            ->assertHeader('Content-Type', 'application/javascript; charset=utf-8');

        $js = $res->getContent();
        $this->assertStringContainsString('const VERSION = '.json_encode(GtrBibCheckController::version()), $js);
        foreach (GtrBibCheckController::precacheUrls() as $url) {
            $this->assertStringContainsString(json_encode($url), $js);
        }
        $this->assertSame(GtrBibCheckController::SCOPE, parse_url(route('gtr.bib-check'), PHP_URL_PATH));
    }

    public function test_kiosk_uses_only_local_fonts_and_images_that_exist(): void
    {
        $html = $this->get(route('gtr.bib-check'))->assertOk()->getContent();
        $this->assertStringNotContainsString('fonts.googleapis.com', $html, 'font harus lokal supaya jalan offline');

        foreach (GtrBibCheckController::fontFaces() as $face) {
            $this->assertStringContainsString($face['url'], $html);
            $this->assertFileExists(public_path(ltrim(parse_url($face['url'], PHP_URL_PATH), '/')));
        }
        foreach (GtrBibCheckController::IMAGES as $img) {
            $this->assertFileExists(public_path($img));
        }
    }
}
