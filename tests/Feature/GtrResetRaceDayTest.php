<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\GtrCategory;
use App\Models\GtrRegistration;
use App\Models\GtrResult;
use App\Models\GtrResultAdjustment;
use App\Models\GtrScanLog;
use App\Models\GtrSetting;
use App\Models\GtrTimingPoint;
use App\Models\Runner;
use App\Models\User;
use App\Services\GtrTimingAdmin;
use App\Services\MarshalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** gtr:reset-race-day — hapus data uji coba race day di production. */
class GtrResetRaceDayTest extends TestCase
{
    use RefreshDatabase;

    private GtrTimingPoint $cp1;

    private GtrCategory $c7;

    private GtrRegistration $budi;

    private User $marshal;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Storage::fake('local');
        $this->travelTo(Carbon::parse('2026-11-20 08:00:00', 'Asia/Makassar'));

        $event = Event::create(['slug' => 'gtr-2026', 'title' => 'GTR 2026', 'date' => '2026-11-29', 'time' => '06:00']);
        $this->cp1 = GtrTimingPoint::create(['code' => 'CP1', 'name' => 'Puncak', 'type' => 'checkpoint']);
        $finish = GtrTimingPoint::create(['code' => 'FINISH', 'name' => 'Finish', 'type' => 'finish']);
        $this->c7 = GtrCategory::create(['name' => '7K', 'slug' => 'gtr-7k', 'distance' => '7 KM', 'bib_prefix' => '7', 'is_active' => true]);
        $this->c7->timingPoints()->attach([
            $this->cp1->id => ['sequence' => 1, 'is_mandatory' => true],
            $finish->id => ['sequence' => 2, 'is_mandatory' => true],
        ]);

        $runner = Runner::create(['first_name' => 'Budi', 'email' => 'budi@test.id', 'password' => 'x']);
        $this->budi = GtrRegistration::create(['runner_id' => $runner->id, 'gtr_category_id' => $this->c7->id, 'full_name' => 'Budi', 'gender' => 'Laki-laki']);
        $this->budi->forceFill(['payment_status' => 'paid', 'bib_number' => '7001'])->saveQuietly();

        $this->marshal = User::create(['name' => 'Marshal', 'username' => 'cp1', 'password' => 'rahasia6', 'role' => User::ROLE_MARSHAL]);
        app(MarshalService::class)->assign($this->marshal, $event->id, $this->cp1->id);
        $this->admin = User::create(['name' => 'Admin', 'username' => 'admin', 'password' => 'rahasia6', 'role' => User::ROLE_ADMIN]);

        GtrSetting::create(['results_published' => false]);
    }

    private function scan(int $ms): array
    {
        return ['uuid' => (string) Str::uuid(), 'bib' => '7001', 'timing_point_id' => $this->cp1->id,
            'device_time_ms' => $ms, 'clock_offset_ms' => 0, 'source' => 'qr'];
    }

    /** Uji coba lengkap: START, scan, koreksi hasil, racepack, publikasi. */
    private function rehearse(): void
    {
        app(GtrTimingAdmin::class)->fireGunAll($this->admin);
        Sanctum::actingAs($this->marshal);
        $this->postJson('/api/timing/scans/batch', ['scans' => [$this->scan(now()->getTimestampMs())]])->assertOk();
        app(GtrTimingAdmin::class)->adjustResult($this->budi->fresh(), 'status', 'dq', 'uji', $this->admin);
        $this->budi->forceFill(['racepack_collected_at' => now(), 'racepack_collected_by' => $this->admin->id, 'race_status' => 'dq'])->saveQuietly();
        $this->marshal->createToken('timing:dev-1', ['timing']);
        GtrSetting::first()->update(['results_published' => true]);
    }

    public function test_reset_clears_rehearsal_data_but_keeps_participants(): void
    {
        $this->rehearse();
        $this->assertGreaterThan(0, GtrScanLog::count());
        $this->assertGreaterThan(0, GtrResult::count());

        $this->artisan('gtr:reset-race-day', ['--force' => true])->assertSuccessful();

        $this->assertSame(0, GtrScanLog::count());
        $this->assertSame(0, GtrResult::count());
        $this->assertSame(0, GtrResultAdjustment::count());
        $this->assertNull($this->c7->fresh()->gun_fired_at);
        $this->assertSame('registered', $this->budi->fresh()->race_status);
        $this->assertFalse(GtrSetting::first()->results_published);
        $this->assertNotNull(GtrSetting::first()->timing_reset_at);

        // Tanpa opsi: racepack & login HP tetap; peserta & BIB tidak disentuh.
        $this->assertNotNull($this->budi->fresh()->racepack_collected_at);
        $this->assertSame(1, $this->marshal->tokens()->count());
        $this->assertSame('7001', $this->budi->fresh()->bib_number);
        $this->assertSame('paid', $this->budi->fresh()->payment_status);

        $this->assertCount(1, Storage::disk('local')->files('gtr-reset'));
    }

    public function test_racepack_and_logout_options(): void
    {
        $this->rehearse();

        $this->artisan('gtr:reset-race-day', ['--force' => true, '--racepack' => true, '--logout' => true])->assertSuccessful();

        $this->assertNull($this->budi->fresh()->racepack_collected_at);
        $this->assertNull($this->budi->fresh()->racepack_collected_by);
        $this->assertSame(0, $this->marshal->tokens()->count());
    }

    public function test_requires_typed_confirmation(): void
    {
        $this->rehearse();

        $this->artisan('gtr:reset-race-day')
            ->expectsQuestion('Ketik RESET untuk melanjutkan', 'ya')
            ->assertFailed();

        $this->assertGreaterThan(0, GtrScanLog::count());
        $this->assertNotNull($this->c7->fresh()->gun_fired_at);
    }

    public function test_after_reset_gun_can_be_fired_again(): void
    {
        $this->rehearse();
        $this->artisan('gtr:reset-race-day', ['--force' => true])->assertSuccessful();

        $this->assertTrue(app(GtrTimingAdmin::class)->fireGunAll($this->admin));
    }

    public function test_stale_queued_scans_and_racepack_from_before_reset_are_rejected(): void
    {
        $before = now()->getTimestampMs();
        $this->travel(1)->minutes();
        $this->artisan('gtr:reset-race-day', ['--force' => true])->assertSuccessful();
        $this->travel(1)->minutes();

        Sanctum::actingAs($this->marshal);
        $old = $this->scan($before);
        $new = $this->scan(now()->getTimestampMs());

        $this->postJson('/api/timing/scans/batch', ['scans' => [$old, $new]])->assertOk()
            ->assertJsonPath('accepted', [$new['uuid']])
            ->assertJsonPath('rejected', [['uuid' => $old['uuid'], 'reason' => 'before_reset']]);
        $this->assertSame(1, GtrScanLog::count());

        // Session memberi tahu scanner jam reset → antrean lokal lama dihapus di HP.
        $this->getJson('/api/timing/session')->assertOk()
            ->assertJsonPath('reset_at_ms', GtrSetting::timingResetAtMs());

        Sanctum::actingAs(User::create(['name' => 'Meja RP', 'username' => 'rp1', 'password' => 'rahasia6', 'role' => User::ROLE_RACEPACK]));
        $this->postJson('/api/timing/racepack/checkin', ['code' => '7001', 'collected_at_ms' => $before])
            ->assertStatus(409)->assertJsonPath('status', 'before_reset');
        $this->assertNull($this->budi->fresh()->racepack_collected_at);

        $this->postJson('/api/timing/racepack/checkin', ['code' => '7001', 'collected_at_ms' => now()->getTimestampMs()])
            ->assertOk()->assertJsonPath('status', 'ok');
    }
}
