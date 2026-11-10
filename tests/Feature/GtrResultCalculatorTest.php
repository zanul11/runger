<?php

namespace Tests\Feature;

use App\Jobs\RecomputeGtrResults;
use App\Models\Event;
use App\Models\GtrCategory;
use App\Models\GtrRegistration;
use App\Models\GtrResult;
use App\Models\GtrResultAdjustment;
use App\Models\GtrSetting;
use App\Models\GtrTimingPoint;
use App\Models\Runner;
use App\Models\User;
use App\Services\MarshalService;
use App\Services\ResultCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Result engine end-to-end: scan masuk lewat API → hasil di gtr_results. */
class GtrResultCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private GtrTimingPoint $start;

    private GtrTimingPoint $cp1;

    private GtrTimingPoint $finish;

    private GtrCategory $c7;

    private User $marshal;

    private User $marshal2;

    private int $gun;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->travelTo(Carbon::parse('2026-11-29 09:00:00', 'Asia/Makassar'));
        $this->gun = Carbon::parse('2026-11-29 06:00:00', 'Asia/Makassar')->getTimestampMs();

        $event = Event::create(['slug' => 'gtr-2026', 'title' => 'GTR 2026', 'date' => '2026-11-29', 'time' => '06:00']);
        $this->start = GtrTimingPoint::create(['code' => 'START', 'name' => 'Start', 'type' => 'start']);
        $this->cp1 = GtrTimingPoint::create(['code' => 'CP1', 'name' => 'Puncak', 'type' => 'checkpoint']);
        $this->finish = GtrTimingPoint::create(['code' => 'FINISH', 'name' => 'Finish', 'type' => 'finish']);

        $this->c7 = GtrCategory::create(['name' => '7K', 'slug' => 'gtr-7k', 'distance' => '7 KM', 'bib_prefix' => '7']);
        $this->c7->forceFill(['gun_fired_at' => $this->gun])->saveQuietly();
        $this->c7->timingPoints()->attach([
            $this->start->id => ['sequence' => 0, 'is_mandatory' => false, 'cutoff_at' => null],
            $this->cp1->id => ['sequence' => 1, 'is_mandatory' => true, 'cutoff_at' => null],
            $this->finish->id => ['sequence' => 2, 'is_mandatory' => true, 'cutoff_at' => '2026-11-29 08:00:00'],
        ]);

        // Dua device/marshal yang masing-masing pegang semua pos (supaya bisa uji sync acak).
        foreach (['marshal' => 'm1', 'marshal2' => 'm2'] as $prop => $username) {
            $this->{$prop} = User::create(['name' => $username, 'username' => $username, 'password' => 'rahasia6', 'role' => User::ROLE_MARSHAL]);
            foreach ([$this->start, $this->cp1, $this->finish] as $tp) {
                app(MarshalService::class)->assign($this->{$prop}, $event->id, $tp->id);
            }
        }
    }

    private function reg(string $bib, string $name, string $gender = 'Laki-laki', string $status = 'paid', ?GtrCategory $cat = null): GtrRegistration
    {
        $runner = Runner::create(['first_name' => $name, 'email' => Str::slug($name).'@t.id', 'password' => 'x']);
        $reg = GtrRegistration::create(['runner_id' => $runner->id, 'gtr_category_id' => ($cat ?? $this->c7)->id, 'full_name' => $name, 'gender' => $gender]);
        $reg->forceFill(['payment_status' => $status, 'bib_number' => $bib])->saveQuietly();

        return $reg->fresh();
    }

    private function at(string $hms): int
    {
        return Carbon::parse('2026-11-29 '.$hms, 'Asia/Makassar')->getTimestampMs();
    }

    /** Scan oleh $user; device_time = waktu sebenarnya − offset (jam device meleset). */
    private function scan(User $user, string $bib, GtrTimingPoint $tp, string $hms, int $offset = 0): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'bib' => $bib,
            'timing_point_id' => $tp->id,
            'device_time_ms' => $this->at($hms) - $offset,
            'clock_offset_ms' => $offset,
            'source' => 'manual',
        ];
    }

    private function send(User $user, array $scans): void
    {
        Sanctum::actingAs($user);
        $this->postJson('/api/timing/scans/batch', ['scans' => $scans])->assertOk();
    }

    private function resultOf(GtrRegistration $reg): GtrResult
    {
        return GtrResult::where('gtr_registration_id', $reg->id)->firstOrFail();
    }

    public function test_batch_triggers_recompute_and_stores_times_ranks_and_checkpoints(): void
    {
        $budi = $this->reg('7001', 'Budi');
        $sari = $this->reg('7002', 'Sari', 'Perempuan');
        $andi = $this->reg('7003', 'Andi');
        $dodi = $this->reg('7004', 'Dodi'); // tidak muncul sama sekali

        $this->send($this->marshal, [
            $this->scan($this->marshal, '7001', $this->start, '06:00:04.500'),
            $this->scan($this->marshal, '7001', $this->cp1, '06:40:00'),
            $this->scan($this->marshal, '7001', $this->finish, '07:10:00.250'),
            $this->scan($this->marshal, '7002', $this->cp1, '06:35:00'),
            $this->scan($this->marshal, '7002', $this->finish, '07:05:00'),
            $this->scan($this->marshal, '7003', $this->start, '06:00:10'),
        ]);

        $b = $this->resultOf($budi);
        $this->assertSame('finisher', $b->status);
        $this->assertSame(4_200_250, $b->gun_time_ms);
        $this->assertSame(4_195_750, $b->net_time_ms);
        $this->assertSame(4_200, $b->gun_time_seconds);
        $this->assertSame('2026-11-29 07:10:00.250', $b->finish_time->format('Y-m-d H:i:s.v'));
        $this->assertSame([2, 1, 2], [$b->rank_overall, $b->rank_gender, $b->rank_category]);
        $this->assertSame($this->c7->id, $b->gtr_category_id);
        $this->assertSame(['START', 'CP1', 'FINISH'], array_column($b->checkpoints_json, 'code'));

        $s = $this->resultOf($sari);
        $this->assertSame([1, 1], [$s->rank_overall, $s->rank_gender]);
        $this->assertNull($s->net_time_ms); // tanpa scan START

        $this->assertSame('dnf', $this->resultOf($andi)->status);
        $this->assertSame('dns', $this->resultOf($dodi)->status);
    }

    public function test_race_status_is_synced_only_after_results_are_published(): void
    {
        $budi = $this->reg('7001', 'Budi');
        $andi = $this->reg('7003', 'Andi'); // masih di lintasan
        $dodi = $this->reg('7004', 'Dodi'); // belum terlihat
        $setting = GtrSetting::current(); // baris pengaturan tunggal (dibuat migration)
        $setting->update(['results_published' => false]);

        $this->send($this->marshal, [
            $this->scan($this->marshal, '7001', $this->cp1, '06:40:00'),
            $this->scan($this->marshal, '7001', $this->finish, '07:10:00'),
            $this->scan($this->marshal, '7003', $this->cp1, '06:50:00'),
        ]);

        // Selama lomba: hasil dihitung, tapi /lacak tidak melihat DNF/DNS.
        $this->assertSame('dnf', $this->resultOf($andi)->status);
        $this->assertSame(['registered', 'registered', 'registered'], [$budi->fresh()->race_status, $andi->fresh()->race_status, $dodi->fresh()->race_status]);

        // Publikasi hasil → hitung ulang + sinkron.
        $setting->update(['results_published' => true]);
        $this->assertSame(['finisher', 'dnf', 'dns'], [$budi->fresh()->race_status, $andi->fresh()->race_status, $dodi->fresh()->race_status]);

        // Setelah publik, scan/koreksi berikutnya langsung tersinkron.
        $this->send($this->marshal, [$this->scan($this->marshal, '7003', $this->finish, '07:30:00')]);
        $this->assertSame('finisher', $andi->fresh()->race_status);
    }

    public function test_random_sync_order_offsets_leading_zero_and_double_scans_give_same_result(): void
    {
        $budi = $this->reg('7001', 'Budi');

        // Device 1: jam 3 detik lebih lambat (offset +3000). Device 2: 2 detik lebih cepat (offset −2000).
        $d1 = [
            $this->scan($this->marshal, '07001', $this->finish, '07:10:00.400', 3_000),   // scan finish ke-2 (ganda)
            $this->scan($this->marshal, ' 7001', $this->cp1, '06:40:00', 3_000),
        ];
        $d2 = [
            $this->scan($this->marshal2, '7001', $this->finish, '07:10:00.100', -2_000), // finish pertama
            $this->scan($this->marshal2, '7001', $this->finish, '07:12:30', -2_000),     // > 60 dtk: sah tapi bukan pertama
            $this->scan($this->marshal2, '007001', $this->start, '06:00:02', -2_000),
        ];

        // Device 1 sync duluan, lalu device 2 (urutan terbalik dari kejadian).
        $this->send($this->marshal, $d1);
        $this->assertSame(4_200_400, $this->resultOf($budi)->gun_time_ms);
        $this->send($this->marshal2, $d2);

        $r = $this->resultOf($budi);
        $this->assertSame('finisher', $r->status);
        $this->assertSame(4_200_100, $r->gun_time_ms);
        $this->assertSame(4_198_100, $r->net_time_ms);

        // Hitung ulang dari nol menghasilkan angka yang sama.
        app(ResultCalculator::class)->recomputeAll();
        $this->assertSame(4_200_100, $this->resultOf($budi)->gun_time_ms);
    }

    public function test_finish_over_cutoff_is_dnf(): void
    {
        $budi = $this->reg('7001', 'Budi');
        $this->send($this->marshal, [
            $this->scan($this->marshal, '7001', $this->cp1, '07:00:00'),
            $this->scan($this->marshal, '7001', $this->finish, '08:00:00.001'),
        ]);

        $this->assertSame('dnf', $this->resultOf($budi)->status);
    }

    public function test_missing_checkpoint_is_dq_for_review_and_not_ranked(): void
    {
        $budi = $this->reg('7001', 'Budi');
        $this->send($this->marshal, [$this->scan($this->marshal, '7001', $this->finish, '07:00:00')]);

        $r = $this->resultOf($budi);
        $this->assertSame('dq', $r->status);
        $this->assertTrue($r->needs_review);
        $this->assertNull($r->rank_overall);
    }

    public function test_cancelled_scan_is_not_counted(): void
    {
        $budi = $this->reg('7001', 'Budi');
        $cp = $this->scan($this->marshal, '7001', $this->cp1, '06:40:00');
        $fin = $this->scan($this->marshal, '7001', $this->finish, '07:10:00');
        $this->send($this->marshal, [$cp, $fin]);
        $this->assertSame('finisher', $this->resultOf($budi)->status);

        $this->send($this->marshal, [$fin + ['cancelled' => true]]);

        $this->assertSame('dnf', $this->resultOf($budi)->status);
    }

    public function test_adjustment_survives_recompute(): void
    {
        $budi = $this->reg('7001', 'Budi');
        $this->send($this->marshal, [$this->scan($this->marshal, '7001', $this->finish, '07:00:00')]);
        $this->assertSame('dq', $this->resultOf($budi)->status);

        GtrResultAdjustment::create([
            'gtr_registration_id' => $budi->id, 'user_id' => $this->marshal->id, 'field' => 'status',
            'old_value' => 'dq', 'new_value' => 'finisher', 'reason' => 'Terlihat di video CP1',
        ]);
        $this->send($this->marshal, [$this->scan($this->marshal, '7001', $this->start, '06:00:01')]);

        $r = $this->resultOf($budi);
        $this->assertSame('finisher', $r->status);
        $this->assertFalse($r->needs_review);
        $this->assertSame(1, $r->rank_overall);
    }

    public function test_firing_gun_recomputes_category(): void
    {
        $this->c7->forceFill(['gun_fired_at' => null, 'gun_start' => null])->saveQuietly();
        $budi = $this->reg('7001', 'Budi');
        $this->send($this->marshal, [
            $this->scan($this->marshal, '7001', $this->cp1, '06:40:00'),
            $this->scan($this->marshal, '7001', $this->finish, '07:10:00'),
        ]);
        $this->assertNull($this->resultOf($budi)->gun_time_ms);
        $this->assertTrue($this->resultOf($budi)->needs_review);

        Sanctum::actingAs(User::create(['name' => 'RD', 'username' => 'rd', 'password' => 'rahasia6', 'role' => User::ROLE_ADMIN]));
        $this->postJson("/api/timing/waves/{$this->c7->id}/start", ['fired_at_ms' => $this->at('06:00:30')])->assertOk();

        $this->assertSame(4_170_000, $this->resultOf($budi)->gun_time_ms);
        $this->assertFalse($this->resultOf($budi)->needs_review);
    }

    public function test_only_race_eligible_participants_get_results(): void
    {
        $budi = $this->reg('7001', 'Budi');
        $this->reg('7002', 'Batal', status: 'cancelled');
        app(ResultCalculator::class)->recomputeAll();
        $this->assertSame(1, GtrResult::count());

        $budi->forceFill(['payment_status' => 'cancelled'])->saveQuietly();
        app(ResultCalculator::class)->recomputeAll();
        $this->assertSame(0, GtrResult::count());
    }

    public function test_concurrent_recompute_is_coalesced_not_lost(): void
    {
        $budi = $this->reg('7001', 'Budi');

        // Proses lain sedang memegang lock → permintaan ini hanya menandai dirty.
        $lock = Cache::lock('gtr:results:lock:'.$this->c7->id, 120);
        $lock->get();
        RecomputeGtrResults::dispatchSync([$this->c7->id]);
        $this->assertSame(0, GtrResult::count());
        $lock->release();

        // Permintaan berikutnya menghitung (dirty dari permintaan sebelumnya ikut terhitung).
        RecomputeGtrResults::dispatchSync([$this->c7->id]);
        $this->assertSame('dns', $this->resultOf($budi)->status);
    }

    public function test_artisan_command(): void
    {
        $budi = $this->reg('7001', 'Budi');

        $this->artisan('timing:recompute', ['--category' => ['gtr-7k']])->assertSuccessful();
        $this->assertSame('dns', $this->resultOf($budi)->status);

        $this->artisan('timing:recompute')->assertSuccessful();
        $this->artisan('timing:recompute', ['--category' => ['tidak-ada']])->assertFailed();
    }
}
