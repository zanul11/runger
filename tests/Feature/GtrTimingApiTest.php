<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\GtrCategory;
use App\Models\GtrRegistration;
use App\Models\GtrScanLog;
use App\Models\GtrTimingPoint;
use App\Models\Runner;
use App\Models\User;
use App\Services\MarshalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** API timing scanner: /api/timing/* */
class GtrTimingApiTest extends TestCase
{
    use RefreshDatabase;

    private Event $event;

    private GtrTimingPoint $start;

    private GtrTimingPoint $cp1;

    private GtrTimingPoint $finish;

    private GtrCategory $c7;

    private GtrCategory $c15;

    private GtrRegistration $budi;   // 7K, BIB 7001

    private GtrRegistration $sari;   // 15K, BIB 5001

    private User $marshal;           // ditugaskan di CP1

    private int $t0;                 // epoch ms acuan

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->travelTo(Carbon::parse('2026-11-29 08:00:00', 'Asia/Makassar'));
        $this->t0 = Carbon::parse('2026-11-29 07:00:00.250', 'Asia/Makassar')->getTimestampMs();

        $this->event = Event::create(['slug' => 'gtr-2026', 'title' => 'GTR 2026', 'date' => '2026-11-29', 'time' => '06:00']);
        $this->start = GtrTimingPoint::create(['code' => 'START', 'name' => 'Start', 'type' => 'start']);
        $this->cp1 = GtrTimingPoint::create(['code' => 'CP1', 'name' => 'Puncak', 'type' => 'checkpoint']);
        $this->finish = GtrTimingPoint::create(['code' => 'FINISH', 'name' => 'Finish', 'type' => 'finish']);

        $this->c7 = GtrCategory::create(['name' => '7K', 'slug' => 'gtr-7k', 'distance' => '7 KM', 'bib_prefix' => '7']);
        $this->c15 = GtrCategory::create(['name' => '15K', 'slug' => 'gtr-15k', 'distance' => '15 KM', 'bib_prefix' => '5']);
        // CP1 hanya dilewati 7K.
        $this->c7->timingPoints()->attach([
            $this->start->id => ['sequence' => 0, 'is_mandatory' => false],
            $this->cp1->id => ['sequence' => 1, 'is_mandatory' => true],
            $this->finish->id => ['sequence' => 2, 'is_mandatory' => true],
        ]);
        $this->c15->timingPoints()->attach([
            $this->start->id => ['sequence' => 0, 'is_mandatory' => false],
            $this->finish->id => ['sequence' => 1, 'is_mandatory' => true],
        ]);

        $this->budi = $this->registration($this->c7, '7001', 'Budi Santoso', 'Laki-laki');
        $this->sari = $this->registration($this->c15, '5001', 'Sari Dewi', 'Perempuan');

        $this->marshal = User::create(['name' => 'Marshal CP1', 'username' => 'cp1', 'password' => 'rahasia6', 'role' => User::ROLE_MARSHAL]);
        app(MarshalService::class)->assign($this->marshal, $this->event->id, $this->cp1->id);
    }

    private function registration(GtrCategory $cat, ?string $bib, string $name, string $gender, string $status = 'paid'): GtrRegistration
    {
        $runner = Runner::create(['first_name' => $name, 'email' => Str::slug($name).'@test.id', 'password' => 'x']);
        $reg = GtrRegistration::create([
            'runner_id' => $runner->id, 'gtr_category_id' => $cat->id, 'full_name' => $name, 'gender' => $gender,
        ]);
        // Tanpa event model → tidak memicu BIB otomatis / email.
        $reg->forceFill(['payment_status' => $status, 'bib_number' => $bib])->saveQuietly();

        return $reg->fresh();
    }

    private function scan(array $overrides = []): array
    {
        return array_merge([
            'uuid' => (string) Str::uuid(),
            'bib' => '7001',
            'timing_point_id' => $this->cp1->id,
            'device_time_ms' => $this->t0,
            'clock_offset_ms' => 0,
            'source' => 'qr',
        ], $overrides);
    }

    private function batch(array ...$scans)
    {
        return $this->postJson('/api/timing/scans/batch', ['scans' => $scans])->assertOk();
    }

    // ---------- session / time ----------

    public function test_session_login_returns_post_categories_roster_and_server_time(): void
    {
        $this->registration($this->c7, null, 'Belum Bayar', 'Laki-laki', 'pending');

        $res = $this->postJson('/api/timing/session', ['username' => 'cp1', 'password' => 'rahasia6', 'device_id' => 'dev-A1'])
            ->assertOk()
            ->assertJsonStructure(['token', 'device_id', 'user', 'timing_point' => ['id', 'code', 'name', 'type'], 'categories', 'participants', 'rules', 'server_time_ms'])
            ->assertJsonPath('timing_point.code', 'CP1')
            ->assertJsonPath('device_id', 'dev-A1');

        $cats = collect($res->json('categories'))->keyBy('name');
        $this->assertTrue($cats['7K']['passes_point']);
        $this->assertFalse($cats['15K']['passes_point']);

        // Hanya peserta lunas + punya BIB.
        $this->assertEqualsCanonicalizing(['7001', '5001'], collect($res->json('participants'))->pluck('bib')->all());
        $p = collect($res->json('participants'))->firstWhere('bib', '5001');
        $this->assertSame(['bib' => '5001', 'registration_number' => $this->sari->nomor_registrasi, 'name' => 'Sari Dewi',
            'category_id' => $this->c15->id, 'gender' => 'F', 'size' => null, 'racepack' => false, 'seen_at_ms' => null, 'seen_passes_ms' => []], $p);

        $this->assertSame(now()->getTimestampMs(), $res->json('server_time_ms'));
        $this->assertSame('dev-A1', $this->marshal->activeAssignment()->device_id);

        // Token dipakai untuk GET session → device id dari token.
        $this->withToken($res->json('token'))->getJson('/api/timing/session')
            ->assertOk()->assertJsonPath('device_id', 'dev-A1')->assertJsonMissingPath('token');
    }

    public function test_session_login_rejects_wrong_password(): void
    {
        $this->postJson('/api/timing/session', ['username' => 'cp1', 'password' => 'salah', 'device_id' => 'dev-A1'])
            ->assertStatus(422);
    }

    public function test_endpoints_require_token(): void
    {
        $this->getJson('/api/timing/time')->assertUnauthorized();
        $this->postJson('/api/timing/scans/batch', ['scans' => []])->assertUnauthorized();
    }

    public function test_time_returns_server_time_in_ms(): void
    {
        Sanctum::actingAs($this->marshal);

        $this->getJson('/api/timing/time')->assertOk()
            ->assertJsonPath('server_time_ms', now()->getTimestampMs())
            ->assertJsonPath('timezone', 'Asia/Makassar');
    }

    public function test_old_api_endpoints_are_gone(): void
    {
        Sanctum::actingAs($this->marshal);

        $this->getJson('/api/roster')->assertNotFound();
        $this->postJson('/api/scans')->assertNotFound();
        $this->postJson('/api/login')->assertNotFound();
    }

    // ---------- scans/batch ----------

    public function test_scan_is_stored_with_corrected_time_in_ms(): void
    {
        Sanctum::actingAs($this->marshal);
        $s = $this->scan(['bib' => ' 07001 ', 'clock_offset_ms' => -1_500]); // jam device 1,5 detik lebih cepat

        $this->batch($s)->assertJsonPath('accepted', [$s['uuid']])->assertJsonPath('rejected', []);

        $log = GtrScanLog::firstOrFail();
        $this->assertSame($this->budi->id, $log->gtr_registration_id);
        $this->assertSame(' 07001 ', $log->bib_input);
        $this->assertSame($this->t0, $log->raw_device_time->getTimestampMs());
        $this->assertSame($this->t0 - 1_500, $log->scanned_at->getTimestampMs());
        $this->assertSame('2026-11-29 06:59:58.750', $log->scanned_at->format('Y-m-d H:i:s.v'));
        $this->assertSame(-1_500, $log->clock_offset_ms);
        $this->assertSame('qr', $log->source);
        $this->assertSame($this->marshal->id, $log->scanned_by);
        $this->assertNotNull($log->server_received_at);
        $this->assertFalse($log->is_ignored);
        $this->assertFalse($log->is_flagged);
    }

    public function test_batch_is_idempotent_by_uuid(): void
    {
        Sanctum::actingAs($this->marshal);
        $s = $this->scan();

        $this->batch($s)->assertJsonPath('accepted', [$s['uuid']]);
        $this->batch($s, $s)->assertJsonPath('accepted', [$s['uuid']]);

        $this->assertSame(1, GtrScanLog::count());
    }

    public function test_unknown_invalid_and_wrong_category_are_stored_as_ignored(): void
    {
        Sanctum::actingAs($this->marshal);
        $unknown = $this->scan(['bib' => '9999']);
        $invalid = $this->scan(['bib' => '70A1']);
        $wrongCat = $this->scan(['bib' => '5001']); // 15K tidak lewat CP1
        $ok = $this->scan();

        $res = $this->batch($unknown, $invalid, $wrongCat, $ok);

        $res->assertJsonPath('accepted', [$ok['uuid']]);
        $this->assertSame([
            ['uuid' => $unknown['uuid'], 'reason' => 'unknown_bib'],
            ['uuid' => $invalid['uuid'], 'reason' => 'invalid_bib'],
            ['uuid' => $wrongCat['uuid'], 'reason' => 'wrong_category'],
        ], $res->json('rejected'));

        $this->assertSame(4, GtrScanLog::count());
        $this->assertNull(GtrScanLog::where('client_uuid', $unknown['uuid'])->value('gtr_registration_id'));
        $this->assertSame($this->sari->id, GtrScanLog::where('client_uuid', $wrongCat['uuid'])->value('gtr_registration_id'));
        $this->assertSame(1, GtrScanLog::valid()->count());
    }

    public function test_bib_of_unpaid_registration_is_unknown(): void
    {
        Sanctum::actingAs($this->marshal);
        $this->registration($this->c7, '7002', 'Batal', 'Laki-laki', 'cancelled');
        $s = $this->scan(['bib' => '7002']);

        $this->batch($s)->assertJsonPath('rejected.0.reason', 'unknown_bib');
    }

    public function test_scan_for_post_not_assigned_is_rejected_and_not_stored(): void
    {
        Sanctum::actingAs($this->marshal);
        $s = $this->scan(['timing_point_id' => $this->finish->id]);

        $this->batch($s)->assertJsonPath('rejected', [['uuid' => $s['uuid'], 'reason' => 'wrong_post']]);
        $this->assertSame(0, GtrScanLog::count());
    }

    public function test_scans_from_previous_post_are_accepted_after_reassign(): void
    {
        app(MarshalService::class)->reassign($this->marshal, $this->event->id, $this->finish->id);
        Sanctum::actingAs($this->marshal);

        $this->batch($this->scan())->assertJsonCount(1, 'accepted'); // antrean offline dari CP1
    }

    public function test_malformed_item_does_not_block_the_rest_of_batch(): void
    {
        Sanctum::actingAs($this->marshal);
        $bad = $this->scan(['device_time_ms' => 'kemarin']);
        $ok = $this->scan();

        $this->batch($bad, $ok)
            ->assertJsonPath('accepted', [$ok['uuid']])
            ->assertJsonPath('rejected', [['uuid' => $bad['uuid'], 'reason' => 'invalid']]);
    }

    public function test_future_corrected_time_is_flagged(): void
    {
        Sanctum::actingAs($this->marshal);
        $this->batch($this->scan(['device_time_ms' => now()->getTimestampMs() + 600_000]));

        $this->assertTrue(GtrScanLog::firstOrFail()->is_flagged);
    }

    // ---------- dedup ----------

    public function test_same_bib_within_60_seconds_is_ignored_as_duplicate(): void
    {
        Sanctum::actingAs($this->marshal);
        $a = $this->scan();
        $b = $this->scan(['device_time_ms' => $this->t0 + 30_000]);
        $c = $this->scan(['device_time_ms' => $this->t0 + 59_999, 'bib' => '7001']);
        $d = $this->scan(['device_time_ms' => $this->t0 + 60_000]);

        $res = $this->batch($a, $b, $c, $d);

        $res->assertJsonPath('accepted', [$a['uuid'], $d['uuid']]);
        $this->assertSame([
            ['uuid' => $b['uuid'], 'reason' => 'duplicate'],
            ['uuid' => $c['uuid'], 'reason' => 'duplicate'],
        ], $res->json('rejected'));
    }

    public function test_dedup_is_independent_of_sync_order(): void
    {
        // Device B (scan lebih belakang) sync duluan, device A (scan lebih awal) menyusul.
        $other = User::create(['name' => 'Marshal CP1 b', 'username' => 'cp1b', 'password' => 'rahasia6', 'role' => User::ROLE_MARSHAL]);
        app(MarshalService::class)->assign($other, $this->event->id, $this->cp1->id);
        $late = $this->scan(['device_time_ms' => $this->t0 + 20_000]);
        $early = $this->scan(['device_time_ms' => $this->t0 + 5_000, 'clock_offset_ms' => -5_000]); // terkoreksi = t0

        Sanctum::actingAs($other);
        $this->batch($late)->assertJsonPath('accepted', [$late['uuid']]);

        Sanctum::actingAs($this->marshal);
        $this->batch($early)->assertJsonPath('accepted', [$early['uuid']]);

        $this->assertFalse(GtrScanLog::where('client_uuid', $early['uuid'])->value('is_ignored'));
        $this->assertSame('duplicate', GtrScanLog::where('client_uuid', $late['uuid'])->value('ignore_reason'));
        $this->assertSame($this->t0, GtrScanLog::valid()->sole()->scanned_at->getTimestampMs());
    }

    public function test_dedup_is_per_post(): void
    {
        app(MarshalService::class)->assign($this->marshal, $this->event->id, $this->finish->id);
        Sanctum::actingAs($this->marshal);

        $this->batch($this->scan(), $this->scan(['timing_point_id' => $this->finish->id, 'device_time_ms' => $this->t0 + 1_000]))
            ->assertJsonCount(2, 'accepted');
    }

    // ---------- batalkan ----------

    public function test_cancel_marks_scan_ignored_and_revives_its_duplicate(): void
    {
        Sanctum::actingAs($this->marshal);
        $a = $this->scan();
        $b = $this->scan(['device_time_ms' => $this->t0 + 20_000]);
        $this->batch($a, $b)->assertJsonPath('rejected.0.reason', 'duplicate');

        $this->batch($a + ['cancelled' => true])->assertJsonPath('rejected', [['uuid' => $a['uuid'], 'reason' => 'cancelled']]);

        $this->assertSame(2, GtrScanLog::count()); // tidak dihapus
        $row = GtrScanLog::where('client_uuid', $a['uuid'])->first();
        $this->assertTrue($row->is_ignored);
        $this->assertSame($this->marshal->id, $row->ignored_by);
        $this->assertSame($b['uuid'], GtrScanLog::valid()->sole()->client_uuid);
    }

    public function test_scan_sent_already_cancelled_is_stored_ignored(): void
    {
        Sanctum::actingAs($this->marshal);
        $a = $this->scan(['cancelled' => true]);

        $this->batch($a)->assertJsonPath('rejected.0.reason', 'cancelled');
        $this->assertSame(1, GtrScanLog::count());
    }

    public function test_marshal_cannot_cancel_someone_elses_scan(): void
    {
        Sanctum::actingAs($this->marshal);
        $a = $this->scan();
        $this->batch($a);

        $other = User::create(['name' => 'Lain', 'username' => 'lain', 'password' => 'rahasia6', 'role' => User::ROLE_MARSHAL]);
        app(MarshalService::class)->assign($other, $this->event->id, $this->cp1->id);
        Sanctum::actingAs($other);

        $this->batch($a + ['cancelled' => true])->assertJsonPath('accepted', [$a['uuid']]);
        $this->assertFalse(GtrScanLog::firstOrFail()->is_ignored);
    }

    // ---------- source ----------

    public function test_unknown_source_is_rejected(): void
    {
        Sanctum::actingAs($this->marshal);
        $tag = $this->scan(['source' => 'pulltag']);
        $manual = $this->scan(['source' => 'manual', 'device_time_ms' => $this->t0 + 120_000]);

        $this->batch($tag, $manual)
            ->assertJsonPath('accepted', [$manual['uuid']])
            ->assertJsonPath('rejected', [['uuid' => $tag['uuid'], 'reason' => 'invalid']]);
    }

    // ---------- session seen_at ----------

    public function test_session_marks_participants_already_seen_at_post(): void
    {
        Sanctum::actingAs($this->marshal);
        $this->batch($this->scan(), $this->scan(['bib' => '5001']));

        $rows = collect($this->getJson('/api/timing/session')->json('participants'))->keyBy('bib');
        $this->assertSame($this->t0, $rows['7001']['seen_at_ms']);
        $this->assertNull($rows['5001']['seen_at_ms']); // scan salah kategori tidak dihitung
    }

    // ---------- waves ----------

    public function test_only_admin_can_fire_start(): void
    {
        Sanctum::actingAs($this->marshal);
        $this->postJson("/api/timing/waves/{$this->c7->id}/start")->assertForbidden();
        $this->assertNull($this->c7->fresh()->gun_fired_at);
    }

    public function test_admin_fires_start_once_unless_forced(): void
    {
        $admin = User::create(['name' => 'RD', 'username' => 'rd', 'password' => 'rahasia6', 'role' => User::ROLE_ADMIN]);
        Sanctum::actingAs($admin);
        $fired = Carbon::parse('2026-11-29 06:00:03.120', 'Asia/Makassar')->getTimestampMs();

        $this->postJson("/api/timing/waves/{$this->c7->id}/start", ['fired_at_ms' => $fired])
            ->assertOk()->assertJsonPath('fired', true)->assertJsonPath('category.gun_fired_at_ms', $fired);

        $cat = $this->c7->fresh();
        $this->assertSame('2026-11-29 06:00:03.120', $cat->gun_fired_at->format('Y-m-d H:i:s.v'));
        $this->assertSame($admin->id, $cat->gun_fired_by);
        $this->assertSame($fired, $cat->officialGunTime()->getTimestampMs());

        $this->postJson("/api/timing/waves/{$this->c7->id}/start")->assertStatus(409)->assertJsonPath('category.gun_fired_at_ms', $fired);

        $this->postJson("/api/timing/waves/{$this->c7->id}/start", ['force' => true])->assertOk();
        $this->assertSame(now()->getTimestampMs(), $this->c7->fresh()->gun_fired_at->getTimestampMs());
    }

    public function test_fired_at_in_future_is_rejected(): void
    {
        Sanctum::actingAs(User::create(['name' => 'RD', 'username' => 'rd', 'password' => 'rahasia6', 'role' => User::ROLE_ADMIN]));

        $this->postJson("/api/timing/waves/{$this->c7->id}/start", ['fired_at_ms' => now()->getTimestampMs() + 3_600_000])
            ->assertStatus(422);
    }

    // ---------- racepack (role terpisah) ----------

    private function racepackStaff(): User
    {
        return User::create(['name' => 'Petugas RP', 'username' => 'rp1', 'password' => 'rahasia6', 'role' => User::ROLE_RACEPACK]);
    }

    public function test_racepack_staff_logs_in_without_timing_point(): void
    {
        $this->racepackStaff();

        $this->postJson('/api/timing/session', ['username' => 'rp1', 'password' => 'rahasia6', 'device_id' => 'rp-dev'])
            ->assertOk()
            ->assertJsonPath('user.role', 'racepack')
            ->assertJsonPath('timing_point', null)
            ->assertJsonCount(2, 'participants');
    }

    public function test_nik_only_sent_to_racepack_staff_not_to_marshal(): void
    {
        $this->budi->forceFill(['nik' => '5201010101900001'])->saveQuietly();
        $this->racepackStaff();

        $rp = $this->postJson('/api/timing/session', ['username' => 'rp1', 'password' => 'rahasia6', 'device_id' => 'rp-dev'])->assertOk();
        $this->assertSame('5201010101900001', collect($rp->json('participants'))->firstWhere('bib', '7001')['nik']);

        $m = $this->postJson('/api/timing/session', ['username' => 'cp1', 'password' => 'rahasia6', 'device_id' => 'm-dev'])->assertOk();
        $this->assertArrayNotHasKey('nik', collect($m->json('participants'))->firstWhere('bib', '7001'));
    }

    public function test_roles_are_separated(): void
    {
        Sanctum::actingAs($this->racepackStaff());
        $this->postJson('/api/timing/scans/batch', ['scans' => [$this->scan()]])->assertForbidden();
        $this->getJson('/api/timing/racepack/lookup?code=7001')->assertOk();

        Sanctum::actingAs($this->marshal);
        $this->getJson('/api/timing/racepack/lookup?code=7001')->assertForbidden();
        $this->postJson('/api/timing/racepack/checkin', ['code' => '7001'])->assertForbidden();
        $this->assertNull($this->budi->fresh()->racepack_collected_at);

        // Admin boleh keduanya (scan timing hanya di pos yang ia pilih — lihat GtrScannerAdminApiTest).
        Sanctum::actingAs(User::create(['name' => 'Admin', 'username' => 'adm', 'password' => 'rahasia6', 'role' => User::ROLE_ADMIN]));
        $this->getJson('/api/timing/racepack/lookup?code=7001')->assertOk();
    }

    public function test_racepack_lookup_shows_detail_without_changing_data(): void
    {
        Sanctum::actingAs($this->racepackStaff());
        $this->budi->forceFill(['size' => 'L', 'club' => 'Runger', 'nik' => '5201010101900001'])->saveQuietly();

        $this->getJson('/api/timing/racepack/lookup?code=07001')
            ->assertOk()
            ->assertJsonPath('status', 'available')
            ->assertJsonPath('participant.name', 'Budi Santoso')
            ->assertJsonPath('participant.size', 'L')
            ->assertJsonPath('participant.club', 'Runger')
            ->assertJsonPath('participant.nik', '5201010101900001')
            ->assertJsonPath('participant.category', '7K');
        $this->assertNull($this->budi->fresh()->racepack_collected_at);

        $this->getJson('/api/timing/racepack/lookup?code=9999')->assertNotFound()->assertJsonPath('status', 'not_found');
    }

    public function test_racepack_checkin_by_bib_and_registration_number(): void
    {
        Sanctum::actingAs($this->racepackStaff());

        $this->postJson('/api/timing/racepack/checkin', ['code' => '07001'])
            ->assertOk()->assertJsonPath('status', 'ok')->assertJsonPath('participant.bib', '7001')
            ->assertJsonPath('participant.racepack_collected_by', 'Petugas RP');
        $this->assertNotNull($this->budi->fresh()->racepack_collected_at);

        $this->postJson('/api/timing/racepack/checkin', ['code' => 7001])->assertOk()->assertJsonPath('status', 'already');
        $this->getJson('/api/timing/racepack/lookup?code=7001')->assertOk()
            ->assertJsonPath('status', 'already')->assertJsonPath('participant.racepack_collected_by', 'Petugas RP');

        $this->postJson('/api/timing/racepack/checkin', ['code' => strtolower($this->sari->nomor_registrasi), 'picked_by' => 'Kakak'])
            ->assertOk()->assertJsonPath('status', 'ok')->assertJsonPath('participant.racepack_picked_by', 'Kakak');
    }

    public function test_racepack_not_found_and_not_paid(): void
    {
        Sanctum::actingAs($this->racepackStaff());
        $pending = $this->registration($this->c7, null, 'Pending', 'Laki-laki', 'pending');

        $this->postJson('/api/timing/racepack/checkin', ['code' => '9999'])->assertNotFound()->assertJsonPath('status', 'not_found');
        $this->postJson('/api/timing/racepack/checkin', ['code' => $pending->nomor_registrasi])->assertStatus(422)->assertJsonPath('status', 'not_paid');
        $this->getJson('/api/timing/racepack/lookup?code='.$pending->nomor_registrasi)->assertStatus(422)->assertJsonPath('status', 'not_paid');
        $this->assertNull($pending->fresh()->racepack_collected_at);
    }
}
