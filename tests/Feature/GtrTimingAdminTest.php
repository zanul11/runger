<?php

namespace Tests\Feature;

use App\Filament\Pages\GtrRaceStart;
use App\Filament\Resources\GtrResultAdjustments\Pages\ListGtrResultAdjustments;
use App\Filament\Resources\GtrResults\Pages\ListGtrResults;
use App\Filament\Resources\GtrScanLogs\Pages\ListGtrScanLogs;
use App\Filament\Resources\RacepackStaff\Pages\CreateRacepackStaff;
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
use App\Services\GtrScanIngestor;
use App\Services\MarshalService;
use App\Services\ResultCalculator;
use App\Support\GtrRaceDayStats;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** Fase 4: aksi admin (Filament), dashboard race day, halaman publik hasil. */
class GtrTimingAdminTest extends TestCase
{
    use RefreshDatabase;

    private GtrTimingPoint $start;

    private GtrTimingPoint $cp1;

    private GtrTimingPoint $finish;

    private GtrCategory $c7;

    private User $admin;

    private User $marshal;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->travelTo(Carbon::parse('2026-11-29 08:00:00', 'Asia/Makassar'));

        $event = Event::create(['slug' => 'gtr-2026', 'title' => 'GTR 2026', 'date' => '2026-11-29', 'time' => '06:00']);
        $this->start = GtrTimingPoint::create(['code' => 'START', 'name' => 'Start', 'type' => 'start']);
        $this->cp1 = GtrTimingPoint::create(['code' => 'CP1', 'name' => 'Puncak', 'type' => 'checkpoint']);
        $this->finish = GtrTimingPoint::create(['code' => 'FINISH', 'name' => 'Finish', 'type' => 'finish']);
        $this->c7 = GtrCategory::create(['name' => '7K', 'slug' => 'gtr-7k', 'distance' => '7 KM', 'bib_prefix' => '7']);
        $this->c7->forceFill(['gun_fired_at' => Carbon::parse('2026-11-29 06:00:00', 'Asia/Makassar')])->saveQuietly();
        $this->c7->timingPoints()->attach([
            $this->start->id => ['sequence' => 0, 'is_mandatory' => false],
            $this->cp1->id => ['sequence' => 1, 'is_mandatory' => true],
            $this->finish->id => ['sequence' => 2, 'is_mandatory' => true],
        ]);

        $this->admin = User::create(['name' => 'Admin', 'username' => 'admin', 'email' => 'a@t.id', 'password' => 'secret', 'role' => User::ROLE_ADMIN]);
        $this->marshal = User::create(['name' => 'Marshal', 'username' => 'm1', 'password' => 'secret', 'role' => User::ROLE_MARSHAL]);
        foreach ([$this->start, $this->cp1, $this->finish] as $tp) {
            app(MarshalService::class)->assign($this->marshal, $event->id, $tp->id);
        }
    }

    private function reg(string $bib, string $name, string $gender = 'Laki-laki'): GtrRegistration
    {
        $runner = Runner::create(['first_name' => $name, 'email' => Str::slug($name).'@t.id', 'password' => 'x']);
        $reg = GtrRegistration::create(['runner_id' => $runner->id, 'gtr_category_id' => $this->c7->id, 'full_name' => $name, 'gender' => $gender]);
        $reg->forceFill(['payment_status' => 'paid', 'bib_number' => $bib])->saveQuietly();

        return $reg->fresh();
    }

    /** Scan lewat ingestor (jalur yang sama dengan API). */
    private function scan(string $bib, GtrTimingPoint $tp, string $hms, string $device = 'dev-a'): GtrScanLog
    {
        $uuid = (string) Str::uuid();
        app(GtrScanIngestor::class)->ingest($this->marshal, $device, [[
            'uuid' => $uuid, 'bib' => $bib, 'timing_point_id' => $tp->id, 'source' => 'manual',
            'device_time_ms' => Carbon::parse('2026-11-29 '.$hms, 'Asia/Makassar')->getTimestampMs(), 'clock_offset_ms' => 0,
        ]]);
        app(ResultCalculator::class)->recomputeCategory($this->c7->id); // di API dipicu ScanBatchController

        return GtrScanLog::where('client_uuid', $uuid)->firstOrFail();
    }

    private function resultOf(GtrRegistration $reg): ?GtrResult
    {
        return GtrResult::where('gtr_registration_id', $reg->id)->first();
    }

    // ---------- halaman admin ----------

    #[DataProvider('pages')]
    public function test_admin_pages_render(string $path): void
    {
        $budi = $this->reg('7001', 'Budi');
        $this->scan('7001', $this->cp1, '06:40:00');
        $this->scan('9999', $this->cp1, '06:41:00');
        app(ResultCalculator::class)->recomputeAll();
        GtrResultAdjustment::create(['gtr_registration_id' => $budi->id, 'field' => 'status', 'new_value' => 'finisher', 'reason' => 'x']);

        $this->actingAs($this->admin)->get($path)->assertOk();
    }

    public static function pages(): array
    {
        return [
            'race start' => ['/admin/gtr-race-start'],
            'race day' => ['/admin/gtr-monitor'],
            'scan logs' => ['/admin/gtr-scan-logs'],
            'results' => ['/admin/gtr-results'],
            'adjustments' => ['/admin/gtr-result-adjustments'],
            'marshals' => ['/admin/marshals'],
            'racepack staff' => ['/admin/petugas-racepack'],
            'racepack staff create' => ['/admin/petugas-racepack/create'],
        ];
    }

    // ---------- start kategori ----------

    public function test_common_start_fires_all_categories_at_same_time_and_recomputes(): void
    {
        $this->c7->forceFill(['gun_fired_at' => null])->saveQuietly();
        $c15 = GtrCategory::create(['name' => '15K', 'slug' => 'gtr-15k', 'distance' => '15 KM', 'bib_prefix' => '5']);
        $budi = $this->reg('7001', 'Budi');
        $this->scan('7001', $this->cp1, '06:40:00');
        $this->scan('7001', $this->finish, '07:10:00');
        $this->actingAs($this->admin);

        Livewire::test(GtrRaceStart::class)->callAction('startAll')->assertHasNoActionErrors();

        $this->assertSame(now()->getTimestampMs(), $this->c7->fresh()->gun_fired_at->getTimestampMs());
        $this->assertSame(now()->getTimestampMs(), $c15->fresh()->gun_fired_at->getTimestampMs()); // jam yang sama
        $this->assertSame($this->admin->id, $c15->fresh()->gun_fired_by);
        $this->assertNotNull($this->resultOf($budi)); // dihitung ulang

        // Tombol START kedua kali tidak menimpa.
        $this->travel(5)->seconds();
        Livewire::test(GtrRaceStart::class)->callAction('startAll');
        $this->assertNotSame(now()->getTimestampMs(), $this->c7->fresh()->gun_fired_at->getTimestampMs());

        // Koreksi jam tembak → semua kategori ikut, gun time peserta berubah.
        Livewire::test(GtrRaceStart::class)
            ->callAction('correctAll', ['fired_at' => '2026-11-29 06:00:30.500'])
            ->assertHasNoActionErrors();
        $this->assertSame('2026-11-29 06:00:30.500', $c15->fresh()->gun_fired_at->format('Y-m-d H:i:s.v'));
        $this->assertSame(4_169_500, $this->resultOf($budi)->gun_time_ms);
        $this->assertNull($this->resultOf($budi)->net_time_ms); // tanpa scan start → net kosong
    }

    // ---------- scan: abaikan / pulihkan / perbaiki BIB ----------

    public function test_ignore_and_restore_scan_via_filament(): void
    {
        $budi = $this->reg('7001', 'Budi');
        $this->scan('7001', $this->cp1, '06:40:00');
        $fin = $this->scan('7001', $this->finish, '07:10:00');
        $this->assertSame('finisher', $this->resultOf($budi)->status);
        $this->actingAs($this->admin);

        Livewire::test(ListGtrScanLogs::class)->callAction(TestAction::make('ignore')->table($fin))->assertHasNoActionErrors();
        $fin->refresh();
        $this->assertTrue($fin->is_ignored);
        $this->assertSame('admin', $fin->ignore_reason);
        $this->assertSame($this->admin->id, $fin->ignored_by);
        $this->assertSame('dnf', $this->resultOf($budi)->status);

        Livewire::test(ListGtrScanLogs::class)->callAction(TestAction::make('restore')->table($fin))->assertHasNoActionErrors();
        $this->assertFalse($fin->fresh()->is_ignored);
        $this->assertSame('finisher', $this->resultOf($budi)->status);
    }

    public function test_relink_typo_bib_to_correct_participant(): void
    {
        $budi = $this->reg('7001', 'Budi');
        $this->scan('7001', $this->cp1, '06:40:00');
        $typo = $this->scan('7010', $this->finish, '07:10:00'); // salah ketik
        $this->assertSame('unknown_bib', $typo->ignore_reason);
        $this->assertSame('dnf', $this->resultOf($budi)->status);
        $this->actingAs($this->admin);

        Livewire::test(ListGtrScanLogs::class)
            ->callAction(TestAction::make('relink')->table($typo), ['bib' => '07001'])
            ->assertHasNoActionErrors();

        $typo->refresh();
        $this->assertSame($budi->id, $typo->gtr_registration_id);
        $this->assertFalse($typo->is_ignored);
        $this->assertSame('7010', $typo->bib_input); // input asli tetap untuk audit
        $this->assertSame('finisher', $this->resultOf($budi)->status);
    }

    public function test_relink_rejects_unknown_bib(): void
    {
        $typo = $this->scan('7010', $this->finish, '07:10:00');
        $this->actingAs($this->admin);

        Livewire::test(ListGtrScanLogs::class)->callAction(TestAction::make('relink')->table($typo), ['bib' => '7777']);

        $this->assertNull($typo->fresh()->gtr_registration_id);
    }

    // ---------- koreksi hasil ----------

    public function test_result_adjustment_is_audited_applied_and_can_be_deactivated(): void
    {
        $budi = $this->reg('7001', 'Budi');
        $this->scan('7001', $this->finish, '07:10:00'); // CP1 terlewat → DQ review
        $this->assertSame('dq', $this->resultOf($budi)->status);
        $this->actingAs($this->admin);

        Livewire::test(ListGtrResults::class)
            ->callAction(TestAction::make('adjust')->table($this->resultOf($budi)), [
                'field' => 'status', 'status' => 'finisher', 'reason' => 'Terlihat di video CP1',
            ])->assertHasNoActionErrors();

        $adj = GtrResultAdjustment::sole();
        $this->assertSame(['status', 'dq', 'finisher', 'Terlihat di video CP1', $this->admin->id], [$adj->field, $adj->old_value, $adj->new_value, $adj->reason, $adj->user_id]);
        $this->assertSame('finisher', $this->resultOf($budi)->status);
        $this->assertFalse($this->resultOf($budi)->needs_review);

        // Koreksi jam finish
        Livewire::test(ListGtrResults::class)
            ->callAction(TestAction::make('adjust')->table($this->resultOf($budi)), [
                'field' => 'finish_time', 'time' => '2026-11-29 07:09:58.250', 'reason' => 'Foto finish',
            ])->assertHasNoActionErrors();
        $this->assertSame(4_198_250, $this->resultOf($budi)->gun_time_ms);

        // Nonaktifkan koreksi status → kembali DQ
        Livewire::test(ListGtrResultAdjustments::class)->callAction(TestAction::make('deactivate')->table($adj))->assertHasNoActionErrors();
        $this->assertFalse($adj->fresh()->is_active);
        $this->assertSame('dq', $this->resultOf($budi)->status);
    }

    public function test_adjustment_requires_reason(): void
    {
        $budi = $this->reg('7001', 'Budi');
        app(ResultCalculator::class)->recomputeAll();
        $this->actingAs($this->admin);

        Livewire::test(ListGtrResults::class)
            ->callAction(TestAction::make('adjust')->table($this->resultOf($budi)), ['field' => 'status', 'status' => 'dq', 'reason' => ''])
            ->assertHasActionErrors(['reason' => 'required']);
        $this->assertSame(0, GtrResultAdjustment::count());
    }

    // ---------- dashboard race day ----------

    public function test_race_day_matrix_devices_and_sweeper(): void
    {
        $this->reg('7001', 'Budi');
        $this->reg('7002', 'Sari', 'Perempuan');
        $this->reg('7003', 'Andi');
        $this->reg('7004', 'Dodi');

        $this->scan('7001', $this->start, '06:00:05', 'dev-start');
        $this->scan('7002', $this->start, '06:00:06', 'dev-start');
        $this->scan('7003', $this->start, '06:00:07', 'dev-start');
        $this->scan('7001', $this->cp1, '06:40:00', 'dev-cp1');
        $this->scan('7001', $this->finish, '07:10:00', 'dev-fin');
        $this->scan('7002', $this->cp1, '06:45:00', 'dev-cp1');

        $m = GtrRaceDayStats::postMatrix();
        $byCode = collect($m['rows'])->keyBy('code');
        $this->assertSame(['seen' => 3, 'total' => 4], $byCode['START']['cells'][$this->c7->id]);
        $this->assertSame(['seen' => 2, 'total' => 4], $byCode['CP1']['cells'][$this->c7->id]);
        $this->assertSame(['seen' => 1, 'total' => 4], $byCode['FINISH']['cells'][$this->c7->id]);

        // Device: dev-start terakhir kirim 07:00?? → server_received_at = now (08:00) saat test → aktif.
        GtrScanLog::where('device_id', 'dev-start')->update(['server_received_at' => now()->subMinutes(25)->format('Y-m-d H:i:s.v')]);
        GtrScanLog::where('device_id', 'dev-cp1')->update(['server_received_at' => now()->subMinutes(12)->format('Y-m-d H:i:s.v')]);
        $states = collect(GtrRaceDayStats::devices())->pluck('state', 'device_id');
        $this->assertSame(['dev-fin' => 'ok', 'dev-cp1' => 'quiet', 'dev-start' => 'stale'], $states->all());

        // Sweeper: Andi (7003) hanya lewat START → belum terlihat di CP1. Sari sudah lewat CP1. Dodi belum tercatat.
        $s = collect(GtrRaceDayStats::notSeenAtLastCp())->firstWhere('category', '7K');
        $this->assertSame('CP1 · Puncak', $s['last_cp']);
        $this->assertSame(['7003'], array_column($s['rows'], 'bib'));
        $this->assertSame('START', $s['rows'][0]['last_point']);
        $this->assertSame([1, 1, 1], [$s['on_course'], $s['finished'], $s['not_started']]);
    }

    // ---------- halaman publik ----------

    public function test_public_results_follow_published_flag_cache_and_gun_time(): void
    {
        $budi = $this->reg('7001', 'Budi');
        $sari = $this->reg('7002', 'Sari', 'Perempuan');
        foreach ([['7001', '07:10:00'], ['7002', '07:05:00']] as [$bib, $fin]) {
            $this->scan($bib, $this->start, '06:00:30');
            $this->scan($bib, $this->cp1, '06:40:00');
            $this->scan($bib, $this->finish, $fin);
        }

        // Belum dipublikasikan → tidak ada tabel.
        $this->get('/gerung-trail-run/results')->assertOk()->assertDontSee('<div data-rs-live>', false);
        $this->get('/results')->assertRedirect('/gerung-trail-run/results');

        GtrSetting::current()->update(['results_published' => true]);
        Cache::flush();

        $res = $this->get('/gerung-trail-run/results')->assertOk();
        $res->assertHeader('Cache-Control', 'max-age=30, public');
        $res->assertSeeInOrder(['Sari', '01:05:00', 'Budi', '01:10:00']); // urut gun time
        $res->assertSee('data-rk-g="1"', false);

        // Cache 30 detik: hasil baru belum terlihat sampai cache kedaluwarsa.
        GtrResult::where('gtr_registration_id', $budi->id)->update(['gun_time_ms' => 1]);
        $this->get('/gerung-trail-run/results')->assertSee('01:10:00');
        $this->travel(31)->seconds();
        $this->get('/gerung-trail-run/results')->assertDontSee('01:10:00');

        // Halaman personal: waktu resmi = gun time, peringkat gender tersimpan.
        $this->get('/gerung-trail-run/results/7002')->assertOk()->assertSee('OFFICIAL TIME')->assertSee('01:05:00');
    }

    // ---------- petugas racepack ----------

    public function test_admin_creates_racepack_staff_with_racepack_role(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(CreateRacepackStaff::class)
            ->fillForm(['name' => 'Petugas Satu', 'username' => 'rp-satu', 'password' => 'rahasia6'])
            ->call('create')
            ->assertHasNoFormErrors();

        $u = User::where('username', 'rp-satu')->sole();
        $this->assertSame(User::ROLE_RACEPACK, $u->role);
        $this->assertFalse($u->canAccessPanel(filament()->getPanel('admin')));
    }
}
