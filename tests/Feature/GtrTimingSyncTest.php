<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\GtrCategory;
use App\Models\GtrRegistration;
use App\Models\GtrScanLog;
use App\Models\GtrTimingPoint;
use App\Models\GtrTimingPointAssignment;
use App\Models\Runner;
use App\Models\User;
use App\Services\GtrTimingSync;
use App\Services\MarshalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GtrTimingSyncTest extends TestCase
{
    use RefreshDatabase;

    private function cat7k(): GtrCategory
    {
        return GtrCategory::create([
            'name' => '7K', 'slug' => '7k', 'distance' => '7 KM', 'total_km' => 7.16,
            'start_time' => '06:30', 'cut_off_time' => '3 Jam',
            'water_stations' => [
                ['type' => 'ws', 'km' => 2.5, 'name' => 'WS1', 'location' => 'Pertigaan Pos 1'],
                ['type' => 'cp', 'km' => 3.57, 'name' => 'CP1', 'location' => 'Puncak Keteri'],
            ],
        ]);
    }

    private function cat15k(): GtrCategory
    {
        return GtrCategory::create([
            'name' => '15K', 'slug' => '15k', 'distance' => '15 KM', 'total_km' => 14.92,
            'start_time' => '06:00', 'cut_off_at' => '2026-11-29 10:00:00',
            'water_stations' => [
                ['type' => 'ws', 'km' => 7.59, 'location' => 'Pertigaan Pos 1'],
                ['type' => 'cop', 'km' => 8, 'location' => 'Basecamp', 'cutoff' => '09:00'],
                ['type' => 'cp', 'km' => 11.34, 'location' => 'Puncak Keteri'],
                ['type' => 'ws', 'km' => 12.9, 'location' => 'Pertigaan Pos 1'],
            ],
        ]);
    }

    public function test_points_are_built_from_course_points_and_shared_by_location(): void
    {
        $c7 = $this->cat7k();
        $c15 = $this->cat15k();

        // Pertigaan, Puncak, Basecamp, FINISH — lokasi sama digabung, tanpa pos START.
        $this->assertSame(4, GtrTimingPoint::count());
        $this->assertSame(0, GtrTimingPoint::where('type', GtrTimingPoint::TYPE_START)->count());
        $pertigaan = GtrTimingPoint::where('name', 'Pertigaan Pos 1')->firstOrFail();
        $this->assertSame(GtrTimingPoint::TYPE_WATER_STATION, $pertigaan->type);
        $this->assertSame(GtrTimingPoint::TYPE_CHECKPOINT, GtrTimingPoint::where('name', 'Puncak Keteri')->value('type'));

        $seq7 = $c7->timingPoints()->orderBy('gtr_category_timing_point.sequence')->pluck('name')->all();
        $this->assertSame(['Pertigaan Pos 1', 'Puncak Keteri', 'Finish'], $seq7);

        // 15K melewati Pertigaan dua kali → satu pos, lewat pertama (km 7.59).
        $tps = $c15->timingPoints()->orderBy('gtr_category_timing_point.sequence')->get();
        $this->assertSame(['Pertigaan Pos 1', 'Basecamp', 'Puncak Keteri', 'Finish'], $tps->pluck('name')->all());
        $this->assertEquals(7.59, (float) $tps[0]->pivot->km);
        // Dua kunjungan di Pertigaan untuk 15K (WS1 & WS2), satu untuk 7K.
        $this->assertSame([['code' => 'WS1', 'km' => 7.59, 'cutoff' => null], ['code' => 'WS2', 'km' => 12.9, 'cutoff' => null]], json_decode($tps[0]->pivot->visits, true));
        $this->assertCount(1, json_decode($c7->timingPoints()->where('name', 'Pertigaan Pos 1')->first()->pivot->visits, true));
        $this->assertFalse((bool) $tps[0]->pivot->is_mandatory);
        $this->assertTrue((bool) $tps[1]->pivot->is_mandatory);

        // Cut-off COP & finish.
        $this->assertStringStartsWith('2026-11-29 09:00', (string) $tps[1]->pivot->cutoff_at);
        $this->assertStringStartsWith('2026-11-29 10:00', (string) $tps[3]->pivot->cutoff_at);
        $this->assertEquals(14.92, (float) $tps[3]->pivot->km);
        $fin7 = $c7->timingPoints()->where('type', 'finish')->first();
        $this->assertStringStartsWith('2026-11-29 09:30', (string) $fin7->pivot->cutoff_at); // 06:30 + 3 jam
    }

    public function test_resync_keeps_point_ids_assignments_and_scans(): void
    {
        $c7 = $this->cat7k();
        $event = Event::create(['slug' => 'gtr', 'title' => 'GTR', 'date' => '2026-11-29', 'time' => '06:00']);
        $puncak = GtrTimingPoint::where('name', 'Puncak Keteri')->firstOrFail();
        $marshal = User::create(['name' => 'M', 'email' => 'm@t.id', 'password' => 'secret', 'role' => User::ROLE_MARSHAL]);
        app(MarshalService::class)->assign($marshal, $event->id, $puncak->id);

        // Admin mengubah km titik → sinkron ulang otomatis lewat hook saved.
        $c7->update(['water_stations' => [
            ['type' => 'ws', 'km' => 2.6, 'location' => 'Pertigaan Pos 1'],
            ['type' => 'cp', 'km' => 3.7, 'location' => 'Puncak Keteri'],
        ]]);

        $this->assertSame($puncak->id, GtrTimingPoint::where('name', 'Puncak Keteri')->value('id'));
        $this->assertEquals(3.7, (float) $c7->timingPoints()->where('gtr_timing_points.id', $puncak->id)->first()->pivot->km);
        $this->assertSame(1, GtrTimingPointAssignment::where('gtr_timing_point_id', $puncak->id)->count());
    }

    public function test_legacy_manual_point_is_adopted(): void
    {
        $legacy = GtrTimingPoint::create(['code' => 'CP1', 'name' => 'Cp1 - Puncak Keteri', 'type' => 'checkpoint']);
        $this->cat7k();

        $this->assertSame($legacy->id, GtrTimingPoint::where('location_key', 'loc:puncak-keteri')->value('id'));
        $this->assertSame('Puncak Keteri', $legacy->fresh()->name);
        $this->assertSame(3, GtrTimingPoint::count()); // Pertigaan, Puncak, Finish
    }

    public function test_sync_without_course_points_leaves_manual_setup_untouched(): void
    {
        GtrTimingPoint::create(['code' => 'CP1', 'name' => 'Manual', 'type' => 'checkpoint']);
        GtrCategory::create(['name' => '7K', 'slug' => '7k', 'distance' => '7 KM']);

        $this->assertSame(['points' => 0, 'categories' => 0], GtrTimingSync::run());
        $this->assertSame(1, GtrTimingPoint::count());
    }

    public function test_scan_records_the_officer(): void
    {
        $c7 = $this->cat7k();
        $event = Event::create(['slug' => 'gtr', 'title' => 'GTR', 'date' => '2026-11-29', 'time' => '06:00']);
        $puncak = GtrTimingPoint::where('name', 'Puncak Keteri')->firstOrFail();
        $marshal = User::create(['name' => 'Marshal Puncak', 'email' => 'mp@t.id', 'password' => 'secret', 'role' => User::ROLE_MARSHAL]);
        app(MarshalService::class)->assign($marshal, $event->id, $puncak->id);
        $runner = Runner::create(['first_name' => 'Budi', 'email' => 'b@t.id', 'password' => 'x']);
        $reg = GtrRegistration::create(['runner_id' => $runner->id, 'gtr_category_id' => $c7->id, 'bib_number' => '701', 'full_name' => 'Budi', 'gender' => 'Laki-laki']);
        $reg->forceFill(['payment_status' => 'paid'])->saveQuietly();

        Sanctum::actingAs($marshal);
        $this->postJson('/api/timing/scans/batch', ['scans' => [[
            'uuid' => (string) Str::uuid(),
            'bib' => '701',
            'timing_point_id' => $puncak->id,
            'device_time_ms' => now()->getTimestampMs(),
            'clock_offset_ms' => 0,
            'source' => 'qr',
        ]]])->assertSuccessful()->assertJsonCount(1, 'accepted');

        $log = GtrScanLog::firstOrFail();
        $this->assertSame($marshal->id, $log->scanned_by);
        $this->assertSame($puncak->id, $log->gtr_timing_point_id);
        $this->assertSame('Marshal Puncak', $log->scanner->name);
    }

    public function test_old_unused_start_point_is_removed_but_used_one_is_kept(): void
    {
        $this->cat7k();
        $old = GtrTimingPoint::create(['code' => 'START', 'location_key' => 'start', 'name' => 'Start', 'type' => 'start']);
        GtrTimingSync::run();
        $this->assertNull($old->fresh());

        // START yang sudah punya scan dipertahankan (audit), tapi tidak terhubung ke kategori mana pun.
        $used = GtrTimingPoint::create(['code' => 'START', 'location_key' => 'start', 'name' => 'Start', 'type' => 'start']);
        $runner = Runner::create(['first_name' => 'Budi', 'email' => 'b2@t.id', 'password' => 'x']);
        $reg = GtrRegistration::create(['runner_id' => $runner->id, 'gtr_category_id' => GtrCategory::first()->id, 'full_name' => 'Budi']);
        GtrScanLog::create(['gtr_registration_id' => $reg->id, 'gtr_timing_point_id' => $used->id, 'scanned_at' => now(), 'client_uuid' => (string) Str::uuid()]);
        GtrTimingSync::run();
        $this->assertNotNull($used->fresh());
        $this->assertSame(0, $used->categories()->count());
    }
}
