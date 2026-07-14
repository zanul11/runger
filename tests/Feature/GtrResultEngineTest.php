<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\GtrCategory;
use App\Models\GtrRegistration;
use App\Models\GtrResult;
use App\Models\GtrScanLog;
use App\Models\GtrTimingPoint;
use App\Models\Runner;
use App\Services\ResultEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class GtrResultEngineTest extends TestCase
{
    use RefreshDatabase;

    private Event $event;
    private GtrTimingPoint $start;
    private GtrTimingPoint $cp1;
    private GtrTimingPoint $finish;
    private GtrCategory $cat;
    private string $tz = 'Asia/Makassar';

    protected function setUp(): void
    {
        parent::setUp();

        $this->event = Event::create([
            'slug' => 'gtr-2026', 'title' => 'GTR 2026', 'date' => '2026-11-29', 'time' => '06:00',
            'default_gun_start' => Carbon::parse('2026-11-29 06:00', $this->tz),
        ]);

        $this->start = GtrTimingPoint::create(['code' => 'START', 'name' => 'Start', 'type' => 'start']);
        $this->cp1 = GtrTimingPoint::create(['code' => 'CP1', 'name' => 'CP1', 'type' => 'checkpoint']);
        $this->finish = GtrTimingPoint::create(['code' => 'FINISH', 'name' => 'Finish', 'type' => 'finish']);

        $this->cat = GtrCategory::create([
            'name' => '7K', 'slug' => '7k', 'distance' => '7 KM',
            'gun_start' => Carbon::parse('2026-11-29 06:00', $this->tz),
        ]);
        $this->cat->timingPoints()->attach([
            $this->start->id => ['sequence' => 1, 'is_mandatory' => true],
            $this->cp1->id => ['sequence' => 2, 'is_mandatory' => true],
            $this->finish->id => ['sequence' => 3, 'is_mandatory' => true],
        ]);
    }

    private function makeReg(string $name, string $gender = 'Laki-laki'): GtrRegistration
    {
        $runner = Runner::create(['first_name' => $name, 'email' => Str::slug($name) . '@t.id', 'password' => 'x']);

        return GtrRegistration::create([
            'runner_id' => $runner->id, 'gtr_category_id' => $this->cat->id,
            'full_name' => $name, 'gender' => $gender, 'bib_number' => (string) $runner->id,
        ]);
    }

    private function scan(GtrRegistration $reg, GtrTimingPoint $tp, string $hhmm): void
    {
        GtrScanLog::create([
            'gtr_registration_id' => $reg->id,
            'gtr_timing_point_id' => $tp->id,
            'scanned_at' => Carbon::parse("2026-11-29 $hhmm", $this->tz),
            'client_uuid' => (string) Str::uuid(),
            'source' => 'scan',
        ]);
    }

    private function compute(): array
    {
        return app(ResultEngine::class)->compute($this->event->fresh());
    }

    public function test_full_course_is_finisher_with_correct_times(): void
    {
        $reg = $this->makeReg('Budi');
        $this->scan($reg, $this->start, '06:00:00');
        $this->scan($reg, $this->cp1, '06:20:00');
        $this->scan($reg, $this->finish, '06:50:00');

        $this->compute();

        $r = $reg->result()->first();
        $this->assertSame(GtrResult::STATUS_FINISHER, $r->status);
        $this->assertSame(3000, $r->net_time_seconds);  // 06:50 - 06:00
        $this->assertSame(3000, $r->gun_time_seconds);   // 06:50 - gun 06:00
        $this->assertSame(GtrRegistration::RACE_FINISHER, $reg->fresh()->race_status);
    }

    public function test_start_but_no_finish_is_dnf(): void
    {
        $reg = $this->makeReg('Andi');
        $this->scan($reg, $this->start, '06:00:00');
        $this->scan($reg, $this->cp1, '06:20:00');

        $this->compute();

        $this->assertSame(GtrResult::STATUS_DNF, $reg->result()->first()->status);
    }

    public function test_missing_mandatory_checkpoint_is_dq(): void
    {
        $reg = $this->makeReg('Cipto');
        $this->scan($reg, $this->start, '06:00:00');
        // CP1 dilewati (potong jalur)
        $this->scan($reg, $this->finish, '06:40:00');

        $this->compute();

        $this->assertSame(GtrResult::STATUS_DQ, $reg->result()->first()->status);
    }

    public function test_reversed_order_is_dq(): void
    {
        $reg = $this->makeReg('Dedi');
        // CP1 discan SEBELUM start (urutan kebalik)
        $this->scan($reg, $this->cp1, '05:50:00');
        $this->scan($reg, $this->start, '06:00:00');
        $this->scan($reg, $this->finish, '06:50:00');

        $this->compute();

        $this->assertSame(GtrResult::STATUS_DQ, $reg->result()->first()->status);
    }

    public function test_no_scans_is_dns(): void
    {
        $reg = $this->makeReg('Eko');

        $this->compute();

        $this->assertSame(GtrResult::STATUS_DNS, $reg->result()->first()->status);
    }

    public function test_finish_after_cutoff_is_dnf(): void
    {
        $this->cat->forceFill(['cut_off_at' => Carbon::parse('2026-11-29 06:45', $this->tz)])->save();

        $reg = $this->makeReg('Feri');
        $this->scan($reg, $this->start, '06:00:00');
        $this->scan($reg, $this->cp1, '06:20:00');
        $this->scan($reg, $this->finish, '06:50:00'); // lewat COT 06:45

        $this->compute();

        $this->assertSame(GtrResult::STATUS_DNF, $reg->result()->first()->status);
    }

    public function test_ranking_overall_category_and_gender(): void
    {
        $fast = $this->makeReg('Fast', 'Laki-laki');   // 40 menit
        $this->scan($fast, $this->start, '06:00:00');
        $this->scan($fast, $this->cp1, '06:15:00');
        $this->scan($fast, $this->finish, '06:40:00');

        $slow = $this->makeReg('Slow', 'Laki-laki');   // 50 menit
        $this->scan($slow, $this->start, '06:00:00');
        $this->scan($slow, $this->cp1, '06:20:00');
        $this->scan($slow, $this->finish, '06:50:00');

        $woman = $this->makeReg('Wati', 'Perempuan');  // 45 menit
        $this->scan($woman, $this->start, '06:00:00');
        $this->scan($woman, $this->cp1, '06:18:00');
        $this->scan($woman, $this->finish, '06:45:00');

        $this->compute();

        $this->assertSame(1, $fast->result()->first()->rank_overall);
        $this->assertSame(2, $woman->result()->first()->rank_overall);
        $this->assertSame(3, $slow->result()->first()->rank_overall);

        // Gender: pria 1 = fast, pria 2 = slow; wanita 1 = wati.
        $this->assertSame(1, $fast->result()->first()->rank_gender);
        $this->assertSame(2, $slow->result()->first()->rank_gender);
        $this->assertSame(1, $woman->result()->first()->rank_gender);
    }
}
