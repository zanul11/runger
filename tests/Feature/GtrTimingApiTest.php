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
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GtrTimingApiTest extends TestCase
{
    use RefreshDatabase;

    private Event $event;
    private GtrTimingPoint $start;
    private GtrTimingPoint $cp1;
    private GtrCategory $cat;
    private GtrRegistration $reg;

    protected function setUp(): void
    {
        parent::setUp();

        $this->event = Event::create([
            'slug' => 'gtr-2026', 'title' => 'GTR 2026', 'date' => '2026-11-29', 'time' => '06:00',
        ]);
        $this->start = GtrTimingPoint::create([
            'event_id' => $this->event->id, 'code' => 'START', 'name' => 'Start', 'type' => 'start',
        ]);
        $this->cp1 = GtrTimingPoint::create([
            'event_id' => $this->event->id, 'code' => 'CP1', 'name' => 'Checkpoint 1', 'type' => 'checkpoint',
        ]);

        $this->cat = GtrCategory::create(['name' => '7K', 'slug' => '7k', 'distance' => '7 KM']);
        $this->cat->timingPoints()->attach([
            $this->start->id => ['sequence' => 1, 'is_mandatory' => true],
            $this->cp1->id => ['sequence' => 2, 'is_mandatory' => true],
        ]);

        $runner = Runner::create(['first_name' => 'Budi', 'email' => 'budi@test.id', 'password' => 'x']);
        $this->reg = GtrRegistration::create([
            'runner_id' => $runner->id,
            'gtr_category_id' => $this->cat->id,
            'bib_number' => '101',
            'full_name' => 'Budi Santoso',
            'gender' => 'Laki-laki',
        ]);
    }

    private function marshalAtCp1(): User
    {
        $marshal = User::create([
            'name' => 'Marshal CP1', 'email' => 'm-cp1@test.id', 'password' => 'secret', 'role' => User::ROLE_MARSHAL,
        ]);
        app(MarshalService::class)->assign($marshal, $this->event->id, $this->cp1->id);

        return $marshal;
    }

    public function test_qr_token_is_auto_generated_on_registration(): void
    {
        $this->assertNotEmpty($this->reg->qr_token);
    }

    public function test_me_returns_locked_active_assignment(): void
    {
        Sanctum::actingAs($this->marshalAtCp1());

        $this->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('active_assignment.code', 'CP1')
            ->assertJsonPath('active_assignment.timing_point_id', $this->cp1->id)
            ->assertJsonPath('user.role', 'marshal');
    }

    public function test_scan_at_correct_post_is_accepted_and_idempotent(): void
    {
        Sanctum::actingAs($this->marshalAtCp1());

        $uuid = '11111111-1111-4111-8111-111111111111';
        $payload = ['scans' => [[
            'client_uuid' => $uuid,
            'qr_token' => $this->reg->qr_token,
            'timing_point_id' => $this->cp1->id,
            'scanned_at' => '2026-11-29T06:30:00+08:00',
            'raw_device_time' => '2026-11-29T06:30:00+08:00',
            'clock_offset_ms' => 1200,
            'source' => 'scan',
        ]]];

        $this->postJson('/api/scans', $payload)
            ->assertOk()
            ->assertJsonPath('accepted.0', $uuid)
            ->assertJsonCount(0, 'rejected');

        // Kirim ulang payload identik -> tetap accepted, tapi hanya 1 baris (idempoten).
        $this->postJson('/api/scans', $payload)->assertOk()->assertJsonPath('accepted.0', $uuid);

        $this->assertSame(1, GtrScanLog::where('client_uuid', $uuid)->count());
        $this->assertSame(1, GtrScanLog::count());
    }

    public function test_scan_at_wrong_post_is_rejected(): void
    {
        Sanctum::actingAs($this->marshalAtCp1());

        $this->postJson('/api/scans', ['scans' => [[
            'client_uuid' => '22222222-2222-4222-8222-222222222222',
            'qr_token' => $this->reg->qr_token,
            'timing_point_id' => $this->start->id, // bukan pos marshal (CP1)
            'scanned_at' => '2026-11-29T06:00:00+08:00',
        ]]])
            ->assertOk()
            ->assertJsonPath('rejected.0.reason', 'wrong_post')
            ->assertJsonCount(0, 'accepted');

        $this->assertSame(0, GtrScanLog::count());
    }

    public function test_unknown_qr_is_rejected(): void
    {
        Sanctum::actingAs($this->marshalAtCp1());

        $this->postJson('/api/scans', ['scans' => [[
            'client_uuid' => '33333333-3333-4333-8333-333333333333',
            'qr_token' => 'GTR-NOTREAL',
            'timing_point_id' => $this->cp1->id,
            'scanned_at' => '2026-11-29T06:30:00+08:00',
        ]]])
            ->assertOk()
            ->assertJsonPath('rejected.0.reason', 'unknown_qr');
    }

    public function test_marshal_without_assignment_cannot_scan(): void
    {
        $marshal = User::create([
            'name' => 'No Pos', 'email' => 'nopos@test.id', 'password' => 'secret', 'role' => User::ROLE_MARSHAL,
        ]);
        Sanctum::actingAs($marshal);

        $this->postJson('/api/scans', ['scans' => [[
            'client_uuid' => '44444444-4444-4444-8444-444444444444',
            'qr_token' => $this->reg->qr_token,
            'timing_point_id' => $this->cp1->id,
            'scanned_at' => '2026-11-29T06:30:00+08:00',
        ]]])->assertStatus(403);
    }

    public function test_admin_can_create_marshal_but_marshal_cannot(): void
    {
        // Marshal ditolak.
        Sanctum::actingAs($this->marshalAtCp1());
        $this->postJson('/api/marshals', [])->assertStatus(403);

        // Admin berhasil + assignment aktif.
        $admin = User::create([
            'name' => 'Admin', 'email' => 'admin@test.id', 'password' => 'secret', 'role' => User::ROLE_ADMIN,
        ]);
        Sanctum::actingAs($admin);

        $this->postJson('/api/marshals', [
            'name' => 'Marshal Baru',
            'email' => 'baru@test.id',
            'password' => 'rahasia6',
            'event_id' => $this->event->id,
            'timing_point_id' => $this->start->id,
        ])
            ->assertCreated()
            ->assertJsonPath('assignment.code', 'START')
            ->assertJsonPath('assignment.is_active', true);
    }

    public function test_reassign_deactivates_old_and_activates_new(): void
    {
        $marshal = $this->marshalAtCp1();
        $admin = User::create([
            'name' => 'Admin', 'email' => 'admin@test.id', 'password' => 'secret', 'role' => User::ROLE_ADMIN,
        ]);
        Sanctum::actingAs($admin);

        $this->putJson("/api/marshals/{$marshal->id}/reassign", [
            'event_id' => $this->event->id,
            'timing_point_id' => $this->start->id,
        ])->assertOk()->assertJsonPath('assignment.code', 'START');

        // Hanya satu assignment aktif per event.
        $this->assertSame(1, $marshal->timingPointAssignments()->where('is_active', true)->count());
        $this->assertSame($this->start->id, $marshal->activeAssignment($this->event->id)->gtr_timing_point_id);
    }
}
