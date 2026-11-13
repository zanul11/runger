<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\GtrCategory;
use App\Models\GtrRegistration;
use App\Models\GtrScanLog;
use App\Models\GtrSetting;
use App\Models\GtrTimingPoint;
use App\Models\Runner;
use App\Models\User;
use App\Services\MarshalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Menu Kontrol scanner (admin): /api/timing/admin/* */
class GtrScannerAdminApiTest extends TestCase
{
    use RefreshDatabase;

    private GtrTimingPoint $cp1;

    private GtrTimingPoint $finish;

    private GtrCategory $c7;

    private GtrRegistration $budi;   // 7K, BIB 7001, L

    private GtrRegistration $sari;   // 7K, BIB 7002, P

    private User $marshal;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Storage::fake('local');
        $this->travelTo(Carbon::parse('2026-11-29 06:00:00', 'Asia/Makassar'));

        $event = Event::create(['slug' => 'gtr-2026', 'title' => 'GTR 2026', 'date' => '2026-11-29', 'time' => '06:00']);
        $start = GtrTimingPoint::create(['code' => 'START', 'name' => 'Start', 'type' => 'start', 'sort_order' => 1]);
        $this->cp1 = GtrTimingPoint::create(['code' => 'CP1', 'name' => 'Puncak', 'type' => 'checkpoint', 'sort_order' => 2]);
        $this->finish = GtrTimingPoint::create(['code' => 'FINISH', 'name' => 'Finish', 'type' => 'finish', 'sort_order' => 3]);
        $this->c7 = GtrCategory::create(['name' => '7K', 'slug' => 'gtr-7k', 'distance' => '7 KM', 'bib_prefix' => '7', 'is_active' => true,
            'gun_start' => Carbon::parse('2026-11-29 06:00', 'Asia/Makassar')]);
        $this->c7->timingPoints()->attach([
            $start->id => ['sequence' => 0, 'is_mandatory' => false],
            $this->cp1->id => ['sequence' => 1, 'is_mandatory' => true],
            $this->finish->id => ['sequence' => 2, 'is_mandatory' => true],
        ]);

        $this->budi = $this->registration('7001', 'Budi Santoso', 'Laki-laki');
        $this->sari = $this->registration('7002', 'Sari Dewi', 'Perempuan');

        $this->marshal = User::create(['name' => 'Marshal', 'username' => 'm1', 'password' => 'rahasia6', 'role' => User::ROLE_MARSHAL]);
        app(MarshalService::class)->assign($this->marshal, $event->id, $this->cp1->id);
        app(MarshalService::class)->assign(User::create(['name' => 'Finish', 'username' => 'm2', 'password' => 'rahasia6', 'role' => User::ROLE_MARSHAL]), $event->id, $this->finish->id);
        $this->admin = User::create(['name' => 'Admin', 'username' => 'admin', 'password' => 'rahasia6', 'role' => User::ROLE_ADMIN]);
        GtrSetting::create(['results_published' => false]);
    }

    private function registration(string $bib, string $name, string $gender): GtrRegistration
    {
        $runner = Runner::create(['first_name' => $name, 'email' => Str::slug($name).'@test.id', 'password' => 'x']);
        $reg = GtrRegistration::create(['runner_id' => $runner->id, 'gtr_category_id' => $this->c7->id, 'full_name' => $name, 'gender' => $gender,
            'whatsapp' => '081234567890', 'emergency_name' => 'Ibu', 'emergency_contact' => '081111111111']);
        $reg->forceFill(['payment_status' => 'paid', 'bib_number' => $bib])->saveQuietly();

        return $reg->fresh();
    }

    private function scanAs(User $user, GtrTimingPoint $point, string $bib, Carbon $at): void
    {
        Sanctum::actingAs($user);
        $this->postJson('/api/timing/scans/batch', ['scans' => [[
            'uuid' => (string) Str::uuid(), 'bib' => $bib, 'timing_point_id' => $point->id,
            'device_time_ms' => $at->getTimestampMs(), 'clock_offset_ms' => 0, 'source' => 'manual',
        ]]])->assertOk();
    }

    public function test_admin_endpoints_are_admin_only(): void
    {
        Sanctum::actingAs($this->marshal);
        $this->getJson('/api/timing/admin/race')->assertForbidden();
        $this->postJson('/api/timing/admin/start')->assertForbidden();
        $this->postJson('/api/timing/admin/reset', ['code' => 'x'])->assertForbidden();
    }

    public function test_admin_must_choose_location_before_scanning(): void
    {
        $scan = fn () => ['uuid' => (string) Str::uuid(), 'bib' => '7001', 'timing_point_id' => $this->cp1->id,
            'device_time_ms' => now()->getTimestampMs(), 'clock_offset_ms' => 0, 'source' => 'manual'];
        $token = $this->postJson('/api/timing/session', ['username' => 'admin', 'password' => 'rahasia6', 'device_id' => 'hp-admin'])
            ->assertOk()->assertJsonPath('timing_point', null)->json('token');
        $points = $this->withToken($token)->getJson('/api/timing/session')->json('points');
        $this->assertSame(['CP1', 'FINISH'], array_column($points, 'code')); // tanpa START

        // Belum memilih pos → scan ditolak (bukan pos admin).
        $s1 = $scan();
        $this->withToken($token)->postJson('/api/timing/scans/batch', ['scans' => [$s1]])->assertOk()
            ->assertJsonPath('rejected.0.reason', 'wrong_post');

        // Pilih CP1 → sesi untuk CP1, scan diterima.
        $this->withToken($token)->postJson('/api/timing/admin/location', ['timing_point_id' => $this->cp1->id])->assertOk()
            ->assertJsonPath('timing_point.code', 'CP1')
            ->assertJsonPath('device_id', 'hp-admin');
        $s2 = $scan();
        $this->withToken($token)->postJson('/api/timing/scans/batch', ['scans' => [$s2]])->assertOk()
            ->assertJsonPath('accepted', [$s2['uuid']]);
        $this->assertSame('hp-admin', GtrScanLog::where('client_uuid', $s2['uuid'])->value('device_id'));

        // Pindah ke meja racepack → lepas pos; racepack boleh.
        $this->withToken($token)->postJson('/api/timing/admin/location', ['timing_point_id' => null])->assertOk()
            ->assertJsonPath('timing_point', null);
        $this->withToken($token)->getJson('/api/timing/racepack/lookup?code=7002')->assertOk()->assertJsonPath('status', 'available');
        $this->withToken($token)->postJson('/api/timing/racepack/checkin', ['code' => '7002'])->assertOk()->assertJsonPath('status', 'ok');
        $this->assertSame($this->admin->id, $this->sari->fresh()->racepack_collected_by);

        // Antrean offline dari pos lama tetap diterima walau pos sudah dilepas (> 60 detik → bukan duplikat).
        $this->travel(2)->minutes();
        $s3 = $scan();
        $this->withToken($token)->postJson('/api/timing/scans/batch', ['scans' => [$s3]])->assertOk()
            ->assertJsonPath('accepted', [$s3['uuid']]);

        $this->withToken($token)->postJson('/api/timing/admin/location', ['timing_point_id' => 99999])->assertStatus(422);

        // Marshal tetap tidak bisa memakai endpoint lokasi admin.
        Sanctum::actingAs($this->marshal);
        $this->postJson('/api/timing/admin/location', ['timing_point_id' => $this->cp1->id])->assertForbidden();
    }

    public function test_manual_status_removes_runner_from_sweep_and_can_be_revoked(): void
    {
        Sanctum::actingAs($this->admin);
        $this->postJson('/api/timing/admin/start')->assertOk();
        $this->travel(30)->minutes();
        // Lintasan: CP1 → FINISH. Tambah CP lain sebelum CP1 supaya ada yang "di lintasan" belum lewat CP terakhir.
        $cp0 = GtrTimingPoint::create(['code' => 'CP0', 'name' => 'Bawah', 'type' => 'checkpoint', 'sort_order' => 1]);
        $this->c7->timingPoints()->attach($cp0->id, ['sequence' => 1, 'is_mandatory' => true, 'km' => 2]);
        $this->c7->timingPoints()->updateExistingPivot($this->cp1->id, ['sequence' => 2]);
        $this->c7->timingPoints()->updateExistingPivot($this->finish->id, ['sequence' => 3]);
        app(MarshalService::class)->assign($this->marshal, \App\Models\Event::first()->id, $cp0->id);
        $this->scanAs($this->marshal, $cp0, '7001', now());
        $this->scanAs($this->marshal, $cp0, '7002', now());

        Sanctum::actingAs($this->admin);
        $this->getJson('/api/timing/admin/sweep')->assertJsonPath('categories.0.on_course', 2);
        $this->getJson('/api/timing/admin/race')->assertJsonPath('badges.sweep', 2);

        $this->postJson('/api/timing/admin/participants/7001/status', ['status' => 'dnf'])->assertStatus(422); // alasan wajib
        $this->postJson('/api/timing/admin/participants/7001/status', ['status' => 'dnf', 'reason' => 'Cedera, dievakuasi medis di CP0'])->assertOk();

        $this->getJson('/api/timing/admin/sweep')->assertJsonPath('categories.0.on_course', 1)
            ->assertJsonPath('categories.0.withdrawn', 1)
            ->assertJsonPath('categories.0.rows.0.bib', '7002');
        $this->getJson('/api/timing/admin/participants/7001')->assertJsonPath('track.status', 'dnf')
            ->assertJsonPath('manual_status.status', 'dnf')
            ->assertJsonPath('manual_status.by', 'Admin');
        $this->getJson('/api/timing/admin/participants?q=budi')->assertJsonPath('participants.0.status', 'dnf');

        $this->postJson('/api/timing/admin/participants/7001/status', ['status' => 'auto'])->assertOk();
        $this->getJson('/api/timing/admin/sweep')->assertJsonPath('categories.0.on_course', 2);
        $this->getJson('/api/timing/admin/participants/7001')->assertJsonPath('manual_status', null)
            ->assertJsonPath('track.status', 'on_course');
        $this->postJson('/api/timing/admin/participants/9999/status', ['status' => 'dq', 'reason' => 'x'])->assertNotFound();
    }

    public function test_checklist_and_alerts_before_start(): void
    {
        Sanctum::actingAs($this->admin);
        $items = collect($this->getJson('/api/timing/admin/checklist')->assertOk()->json('items'))->keyBy('key');

        $this->assertSame('ok', $items['participants']['state']);
        $this->assertSame('ok', $items['schedule']['state']);
        $this->assertSame('ok', $items['marshals']['state']);          // CP1 & FINISH punya marshal
        $this->assertSame('warn', $items['logins']['state']);          // belum ada yang login
        $this->assertSame('ok', $items['clean']['state']);
        $this->assertSame('info', $items['racepack']['state']);

        // Sisa uji coba: scan sebelum START → peringatan di checklist & beranda.
        $this->scanAs($this->marshal, $this->cp1, '7001', now());
        Sanctum::actingAs($this->admin);
        $this->assertSame('warn', collect($this->getJson('/api/timing/admin/checklist')->json('items'))->firstWhere('key', 'clean')['state']);
        $race = $this->getJson('/api/timing/admin/race')->assertOk();
        $this->assertSame(2, $race->json('badges.checklist'));
        $this->assertSame('checklist', $race->json('alerts.0.view'));

        // Marshal login → item login ok.
        $this->postJson('/api/timing/session', ['username' => 'm1', 'password' => 'rahasia6', 'device_id' => 'hp-m1'])->assertOk();
        $this->postJson('/api/timing/session', ['username' => 'm2', 'password' => 'rahasia6', 'device_id' => 'hp-m2'])->assertOk();
        Sanctum::actingAs($this->admin);
        $this->assertSame('ok', collect($this->getJson('/api/timing/admin/checklist')->json('items'))->firstWhere('key', 'logins')['state']);
    }

    public function test_racepack_pickup_detail_is_visible_to_admin_not_marshal(): void
    {
        $staff = User::create(['name' => 'Rina', 'username' => 'rp1', 'password' => 'rahasia6', 'role' => User::ROLE_RACEPACK]);
        Sanctum::actingAs($staff);
        $at = Carbon::parse('2026-11-27 09:12:30', 'Asia/Makassar')->getTimestampMs();
        $this->travelTo(Carbon::parse('2026-11-27 09:13:00', 'Asia/Makassar'));
        $this->postJson('/api/timing/racepack/checkin', ['code' => '7001', 'picked_by' => 'Andi', 'collected_at_ms' => $at])->assertOk();

        Sanctum::actingAs($this->admin);
        $p = collect($this->getJson('/api/timing/session')->assertOk()->json('participants'))->keyBy('bib');
        $this->assertSame($at, $p['7001']['racepack_at_ms']);
        $this->assertSame('Rina', $p['7001']['racepack_by']);
        $this->assertSame('Andi', $p['7001']['racepack_picked_by']);
        $this->assertNull($p['7002']['racepack_at_ms']);

        $this->getJson('/api/timing/admin/participants/7001')->assertOk()
            ->assertJsonPath('racepack.collected', true)
            ->assertJsonPath('racepack.at_ms', $at)
            ->assertJsonPath('racepack.by', 'Rina')
            ->assertJsonPath('racepack.picked_by', 'Andi');

        // Marshal timing tidak menerima data pribadi / detail racepack.
        Sanctum::actingAs($this->marshal);
        $m = collect($this->getJson('/api/timing/session')->json('participants'))->firstWhere('bib', '7001');
        $this->assertArrayNotHasKey('racepack_by', $m);
        $this->assertTrue($m['racepack']);
    }

    public function test_start_from_phone_uses_device_time_and_second_press_is_rejected(): void
    {
        Sanctum::actingAs($this->admin);
        $this->getJson('/api/timing/admin/race')->assertOk()->assertJsonPath('fired_at_ms', null)
            ->assertJsonPath('categories.0.eligible', 2);

        $at = now()->subSeconds(3)->getTimestampMs() + 123;
        $this->postJson('/api/timing/admin/start', ['fired_at_ms' => $at])->assertOk()
            ->assertJsonPath('fired', true)->assertJsonPath('fired_at_ms', $at)
            ->assertJsonPath('categories.0.gun_fired_by', 'Admin');

        $this->postJson('/api/timing/admin/start')->assertStatus(409)->assertJsonPath('fired', false)
            ->assertJsonPath('fired_at_ms', $at);
    }

    public function test_correct_gun_time_on_race_date(): void
    {
        Sanctum::actingAs($this->admin);
        $this->postJson('/api/timing/admin/start')->assertOk();

        $this->postJson('/api/timing/admin/start/correct', ['time' => '05:59:58.500'])->assertOk();
        $this->assertSame('2026-11-29 05:59:58.500', $this->c7->fresh()->gun_fired_at->format('Y-m-d H:i:s.v'));

        $this->postJson('/api/timing/admin/start/correct', ['time' => '09:00'])->assertStatus(422);
        $this->postJson('/api/timing/admin/start/correct', ['time' => 'jam 6'])->assertStatus(422);
    }

    public function test_posts_sweep_track_and_finish_board(): void
    {
        Sanctum::actingAs($this->admin);
        $this->postJson('/api/timing/admin/start')->assertOk();

        $this->travel(40)->minutes();
        $this->scanAs($this->marshal, $this->cp1, '7001', now()->subMinutes(10));
        $this->scanAs($this->marshal, $this->cp1, '7002', now()->subMinutes(5));
        $finisher = User::where('username', 'm2')->first();
        $this->scanAs($finisher, $this->finish, '7001', now());

        Sanctum::actingAs($this->admin);

        $posts = $this->getJson('/api/timing/admin/posts')->assertOk();
        $cp1 = collect($posts->json('rows'))->firstWhere('code', 'CP1');
        $this->assertSame(['category_id' => $this->c7->id, 'seen' => 2, 'total' => 2], $cp1['cells'][0]);
        $this->assertSame(['Marshal'], $cp1['marshals']);
        $this->assertIsArray($posts->json('devices')); // Sanctum::actingAs tanpa token device → kosong

        // Sari sudah lewat CP1 (CP terakhir) → bukan target sweep; Budi sudah finish.
        $this->getJson('/api/timing/admin/sweep')->assertOk()
            ->assertJsonPath('categories.0.finished', 1)
            ->assertJsonPath('categories.0.on_course', 0);

        $this->getJson('/api/timing/admin/participants?q=sari')->assertOk()
            ->assertJsonPath('participants.0.bib', '7002')
            ->assertJsonPath('participants.0.status', 'on_course')
            ->assertJsonPath('participants.0.last_point', 'CP1');
        $this->getJson('/api/timing/admin/participants?q=700')->assertOk()->assertJsonCount(2, 'participants');

        $detail = $this->getJson('/api/timing/admin/participants/7001')->assertOk()
            ->assertJsonPath('contact.emergency_name', 'Ibu')
            ->assertJsonPath('result.status', 'finisher')
            ->assertJsonCount(2, 'scans');
        $this->assertSame('finished', $detail->json('track.status'));
        $this->getJson('/api/timing/admin/participants/9999')->assertNotFound();

        $this->getJson('/api/timing/admin/finish')->assertOk()
            ->assertJsonPath('categories.0.finishers', 1)
            ->assertJsonPath('categories.0.podium.M.0.bib', '7001')
            ->assertJsonPath('categories.0.podium.F', [])
            ->assertJsonPath('categories.0.latest.0.name', 'Budi Santoso');
    }

    public function test_issues_and_scan_actions(): void
    {
        Sanctum::actingAs($this->admin);
        $this->postJson('/api/timing/admin/start')->assertOk();
        $this->travel(30)->minutes();
        $this->scanAs($this->marshal, $this->cp1, '7099', now()); // salah ketik → tak terdaftar

        Sanctum::actingAs($this->admin);
        $issues = $this->getJson('/api/timing/admin/issues')->assertOk();
        $this->assertSame('unknown_bib', $issues->json('unmatched.0.reason'));
        $scanId = $issues->json('unmatched.0.id');
        $this->getJson('/api/timing/admin/race')->assertJsonPath('issues', 1);

        $this->postJson("/api/timing/admin/scans/$scanId/relink", ['bib' => '9999'])->assertStatus(422);
        $this->postJson("/api/timing/admin/scans/$scanId/relink", ['bib' => '7002'])->assertOk()
            ->assertJsonPath('scan.bib', '7002')->assertJsonPath('scan.is_ignored', false);
        $this->assertSame($this->sari->id, GtrScanLog::find($scanId)->gtr_registration_id);

        $this->postJson("/api/timing/admin/scans/$scanId/ignore")->assertOk()->assertJsonPath('scan.reason', 'admin');
        $this->getJson('/api/timing/admin/issues')->assertJsonPath('ignored.0.id', $scanId);
        $this->postJson("/api/timing/admin/scans/$scanId/restore")->assertOk()->assertJsonPath('scan.is_ignored', false);
        $this->postJson("/api/timing/admin/scans/$scanId/hapus")->assertNotFound();
    }

    public function test_point_scans_list_missing_and_manual_scan(): void
    {
        Sanctum::actingAs($this->admin);
        $this->postJson('/api/timing/admin/start')->assertOk();
        $this->travel(30)->minutes();
        $this->scanAs($this->marshal, $this->cp1, '7001', now()->subMinutes(2));
        $this->scanAs($this->marshal, $this->cp1, '7099', now()->subMinute()); // salah ketik

        Sanctum::actingAs($this->admin);
        $res = $this->getJson("/api/timing/admin/points/{$this->cp1->id}/scans")->assertOk()
            ->assertJsonPath('point.code', 'CP1')
            ->assertJsonPath('stats.valid', 1)
            ->assertJsonPath('stats.ignored', 1)
            ->assertJsonPath('stats.expected', 2)
            ->assertJsonPath('stats.missing', 1)
            ->assertJsonPath('missing.0.bib', '7002')
            ->assertJsonCount(2, 'scans');
        $this->assertSame('7099', $res->json('scans.0.bib_input')); // terbaru dulu
        $this->getJson("/api/timing/admin/points/{$this->cp1->id}/scans?filter=valid")->assertJsonCount(1, 'scans');
        $this->getJson("/api/timing/admin/points/{$this->cp1->id}/scans?q=budi")->assertJsonCount(1, 'scans');

        // Posts menyertakan id pos untuk membuka daftar scan.
        $this->assertSame($this->cp1->id, collect($this->getJson('/api/timing/admin/posts')->json('rows'))->firstWhere('code', 'CP1')['id']);

        // Sari lewat CP1 tapi tidak ter-scan → admin menambah scan manual.
        $time = now()->subMinutes(3)->format('H:i:s');
        $this->postJson("/api/timing/admin/points/{$this->cp1->id}/scans", ['bib' => '7002', 'time' => $time])->assertOk()
            ->assertJsonPath('scan.bib', '7002')
            ->assertJsonPath('scan.source', 'admin')
            ->assertJsonPath('scan.scanner', 'Admin');
        $log = GtrScanLog::where('source', 'admin')->firstOrFail();
        $this->assertSame($this->sari->id, $log->gtr_registration_id);
        $this->assertSame(now()->subMinutes(3)->format('Y-m-d H:i:s'), $log->scanned_at->format('Y-m-d H:i:s'));
        $this->getJson("/api/timing/admin/points/{$this->cp1->id}/scans")->assertJsonPath('stats.missing', 0);

        // Validasi: BIB tak terdaftar, kategori tidak lewat pos, jam di masa depan, format jam.
        $this->postJson("/api/timing/admin/points/{$this->cp1->id}/scans", ['bib' => '9999', 'time' => $time])->assertStatus(422);
        $start = GtrTimingPoint::where('code', 'START')->first();
        $other = GtrTimingPoint::create(['code' => 'X', 'name' => 'Lain', 'type' => 'checkpoint']);
        $this->postJson("/api/timing/admin/points/{$other->id}/scans", ['bib' => '7002', 'time' => $time])->assertStatus(422);
        $this->postJson("/api/timing/admin/points/{$this->cp1->id}/scans", ['bib' => '7002', 'time' => now()->addHour()->format('H:i')])->assertStatus(422);
        $this->postJson("/api/timing/admin/points/{$this->cp1->id}/scans", ['bib' => '7002', 'time' => 'tadi'])->assertStatus(422);
        $this->assertNotNull($start);

        // Scan sah tapi salah orang (7001 seharusnya 7002) → pindahkan ke BIB yang benar.
        $wrong = GtrScanLog::where('gtr_registration_id', $this->budi->id)->where('source', 'manual')->firstOrFail();
        $this->postJson("/api/timing/admin/scans/{$wrong->id}/relink", ['bib' => '7002'])->assertOk()->assertJsonPath('scan.bib', '7002');
        $this->getJson("/api/timing/admin/points/{$this->cp1->id}/scans")->assertJsonPath('missing.0.bib', '7001');

        Sanctum::actingAs($this->marshal);
        $this->getJson("/api/timing/admin/points/{$this->cp1->id}/scans")->assertForbidden();
    }

    public function test_reset_from_app_requires_configured_code(): void
    {
        Sanctum::actingAs($this->admin);
        $this->postJson('/api/timing/admin/start')->assertOk();

        config(['services.gtr.reset_code' => null]);
        $this->getJson('/api/timing/admin/reset')->assertOk()->assertJsonPath('enabled', false);
        $this->postJson('/api/timing/admin/reset', ['code' => '1234'])->assertForbidden();

        config(['services.gtr.reset_code' => '482910']);
        $this->getJson('/api/timing/admin/reset')->assertOk()->assertJsonPath('enabled', true)
            ->assertJsonPath('counts.Kategori sudah di-START', 1);
        $this->postJson('/api/timing/admin/reset', ['code' => '000000'])->assertStatus(422);
        $this->assertNotNull($this->c7->fresh()->gun_fired_at);
    }

    public function test_reset_from_app_keeps_this_phone_logged_in(): void
    {
        config(['services.gtr.reset_code' => '482910']);
        $this->admin->createToken('timing:admin-hp', ['timing']);
        $this->marshal->createToken('timing:marshal-hp', ['timing']);
        $adminToken = $this->postJson('/api/timing/session', ['username' => 'admin', 'password' => 'rahasia6', 'device_id' => 'admin-hp'])->json('token');

        $this->withToken($adminToken)->postJson('/api/timing/admin/start')->assertOk();
        $this->withToken($adminToken)->postJson('/api/timing/admin/reset', ['code' => '482910', 'logout' => true, 'racepack' => true])->assertOk()
            ->assertJsonPath('counts.Kategori sudah di-START', 1);

        $this->assertNull($this->c7->fresh()->gun_fired_at);
        $this->assertSame(0, $this->marshal->tokens()->count());
        $this->assertSame(1, $this->admin->tokens()->count());
        $this->app['auth']->forgetGuards();
        $this->withToken($adminToken)->getJson('/api/timing/admin/race')->assertOk()->assertJsonPath('fired_at_ms', null);
    }
}
