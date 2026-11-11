<?php

namespace Tests\Feature;

use App\Http\Controllers\GtrResultCheckController;
use App\Models\GtrCategory;
use App\Models\GtrRegistration;
use App\Models\GtrResult;
use App\Models\GtrSetting;
use App\Models\Runner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Kiosk Cek Hasil publik: roster Cek BIB + hasil lomba; sebelum publikasi hanya finisher (sementara). */
class GtrResultCheckTest extends TestCase
{
    use RefreshDatabase;

    private GtrCategory $cat;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cat = GtrCategory::create(['name' => '7K', 'slug' => 'gtr-7k', 'distance' => '7 KM', 'color' => '#35E656', 'is_active' => true, 'sort_order' => 1]);
        $this->cat->forceFill(['total_km' => 7])->saveQuietly();

        // [bib, gender, status, gun ms, net ms, rank, rank gender]
        foreach ([
            ['7001', 'Laki-laki', 'finisher', 3_000_000, 2_940_000, 1, 1],
            ['7002', 'Perempuan', 'finisher', 3_600_000, null, 2, 1],
            ['7003', 'Laki-laki', 'dnf', null, null, null, null],
            ['7004', 'Perempuan', null, null, null, null, null],      // belum ada hasil
        ] as $i => [$bib, $g, $status, $gun, $net, $rank, $rankG]) {
            $runner = Runner::create(['first_name' => 'Pelari'.$i, 'email' => "r{$i}@t.id", 'password' => 'x']);
            $reg = GtrRegistration::create([
                'runner_id' => $runner->id, 'gtr_category_id' => $this->cat->id, 'full_name' => 'Peserta '.$i, 'gender' => $g,
                'bib_number' => $bib, 'payment_status' => 'paid', 'nik' => '520101010190000'.$i,
                'emergency_name' => 'Ibu', 'emergency_contact' => '6281234567890',
            ]);
            if ($status) {
                GtrResult::create(['gtr_registration_id' => $reg->id, 'gtr_category_id' => $this->cat->id, 'status' => $status,
                    'gun_time_ms' => $gun, 'net_time_ms' => $net, 'rank_overall' => $rank, 'rank_category' => $rank, 'rank_gender' => $rankG]);
            }
        }
    }

    /** Tanpa event model (saved → recomputeAll akan menghitung ulang dari scan yang memang tidak ada). */
    private function publish(bool $on): void
    {
        GtrSetting::query()->update(['results_published' => $on]);
    }

    public function test_kiosk_page_is_public_in_result_mode(): void
    {
        $this->get(route('gtr.result-check'))->assertOk()
            ->assertSee('Cek hasil lari')->assertSee('RESULT CHECK')
            ->assertSee(json_encode(route('gtr.result-check.data')), false)
            ->assertSee(json_encode(route('gtr.result-check.sw')), false);
    }

    public function test_published_results_show_finisher_times_ranks_and_dnf(): void
    {
        $this->publish(true);

        $res = $this->getJson(route('gtr.result-check.data'))->assertOk()
            ->assertJsonPath('published', true)
            ->assertJsonPath('count', 4)
            ->assertJsonPath('results', 3)
            ->assertJsonPath('runners.7001.result', [
                'status' => 'finisher', 'gun' => '00:50:00', 'net' => '00:49:00', 'pace' => '7:00',
                'rank' => 1, 'rank_total' => 2, 'rank_gender' => 1, 'gender_total' => 1,
            ])
            ->assertJsonPath('runners.7002.result.net', null)
            ->assertJsonPath('runners.7002.result.pace', '8:34') // tanpa net → gun time
            ->assertJsonPath('runners.7003.result.status', 'dnf')
            ->assertJsonPath('runners.7003.result.gun', null)
            ->assertJsonPath('runners.7004.result', null);

        $body = $res->getContent();
        foreach (['5201010101900', '6281234567890', 'r0@t.id'] as $secret) {
            $this->assertStringNotContainsString($secret, $body);
        }
    }

    public function test_unpublished_results_only_expose_finishers(): void
    {
        $this->publish(false);

        $this->getJson(route('gtr.result-check.data'))->assertOk()
            ->assertJsonPath('published', false)
            ->assertJsonPath('results', 2)
            ->assertJsonPath('runners.7001.result.gun', '00:50:00')
            ->assertJsonPath('runners.7003.result', null); // masih di lintasan = DNF sementara → disembunyikan
    }

    public function test_bib_check_data_is_unchanged_by_result_mode(): void
    {
        $this->publish(true);
        $this->getJson(route('gtr.result-check.data'))->assertOk();

        $res = $this->getJson(route('gtr.bib-check.data'))->assertOk()->assertJsonMissingPath('published');
        $this->assertSame(['bib', 'name', 'gender', 'category_id'], array_keys($res->json('runners.7001')));
    }

    public function test_service_worker_uses_own_scope_and_cache(): void
    {
        $js = $this->get(route('gtr.result-check.sw'))->assertOk()
            ->assertHeader('Service-Worker-Allowed', GtrResultCheckController::SCOPE)
            ->getContent();

        $this->assertStringContainsString('const PREFIX = "gtr-resultcheck-"', $js);
        $this->assertStringContainsString('const PAGE = '.json_encode(route('gtr.result-check')), $js);
        $this->assertStringContainsString('const DATA = '.json_encode(route('gtr.result-check.data')), $js);
        $this->assertSame(GtrResultCheckController::SCOPE, parse_url(route('gtr.result-check'), PHP_URL_PATH));
        $this->assertContains(route('gtr.result-check'), GtrResultCheckController::precacheUrls());
    }
}
