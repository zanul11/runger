<?php

namespace Tests\Unit;

use App\Services\ResultCalculator;
use PHPUnit\Framework\TestCase;

/** Aturan status & waktu ResultCalculator::evaluate() / rank() — tanpa database. */
class ResultCalculatorTest extends TestCase
{
    private const GUN = 1_795_903_200_000; // 2026-11-29 06:00:00.000 WITA

    private const START = 1;

    private const CP1 = 2;

    private const WS = 3;

    private const FINISH = 4;

    private function route(?int $finishCutoff = null, ?int $cp1Cutoff = null): array
    {
        return [
            ['id' => self::START, 'code' => 'START', 'name' => 'Start', 'type' => 'start', 'sequence' => 0, 'mandatory' => false, 'cutoff_ms' => null],
            ['id' => self::CP1, 'code' => 'CP1', 'name' => 'Puncak', 'type' => 'checkpoint', 'sequence' => 1, 'mandatory' => true, 'cutoff_ms' => $cp1Cutoff],
            ['id' => self::WS, 'code' => 'WS1', 'name' => 'Water', 'type' => 'water_station', 'sequence' => 2, 'mandatory' => false, 'cutoff_ms' => null],
            ['id' => self::FINISH, 'code' => 'FINISH', 'name' => 'Finish', 'type' => 'finish', 'sequence' => 3, 'mandatory' => true, 'cutoff_ms' => $finishCutoff],
        ];
    }

    private function min(float $minutes): int
    {
        return self::GUN + (int) round($minutes * 60_000);
    }

    private function eval(array $scans, ?int $gun = self::GUN, array $adj = [], ?int $finishCutoff = null, ?int $cp1Cutoff = null): array
    {
        return ResultCalculator::evaluate($this->route($finishCutoff, $cp1Cutoff), $gun, $scans, $adj);
    }

    public function test_full_course_is_finisher_with_gun_and_net_time_in_ms(): void
    {
        $r = $this->eval([
            [self::START, self::GUN + 5_500],
            [self::CP1, $this->min(40)],
            [self::WS, $this->min(55)],
            [self::FINISH, $this->min(70) + 250],
        ]);

        $this->assertSame('finisher', $r['status']);
        $this->assertSame(4_200_250, $r['gun_time_ms']);       // 1:10:00.250
        $this->assertSame(4_200_250 - 5_500, $r['net_time_ms']); // dikurangi telat start 5,5 detik
        $this->assertSame(self::GUN + 5_500, $r['start_ms']);
        $this->assertFalse($r['needs_review']);
        $this->assertNull($r['review_note']);
        $this->assertSame([0 + 5_500, 2_400_000, 3_300_000, 4_200_250], array_column($r['checkpoints'], 'elapsed_ms'));
    }

    public function test_no_time_record_at_all_is_dns(): void
    {
        $r = $this->eval([]);

        $this->assertSame('dns', $r['status']);
        $this->assertNull($r['gun_time_ms']);
    }

    public function test_only_start_scan_is_dnf(): void
    {
        $this->assertSame('dnf', $this->eval([[self::START, self::GUN + 1_000]])['status']);
    }

    public function test_checkpoint_without_finish_is_dnf(): void
    {
        $this->assertSame('dnf', $this->eval([[self::CP1, $this->min(40)]])['status']);
    }

    public function test_finish_after_cutoff_is_dnf(): void
    {
        $r = $this->eval([[self::CP1, $this->min(40)], [self::FINISH, $this->min(121)]], finishCutoff: $this->min(120));

        $this->assertSame('dnf', $r['status']);
        $this->assertSame('Finish melewati cut-off', $r['review_note']);
    }

    public function test_finish_exactly_at_cutoff_is_finisher(): void
    {
        $r = $this->eval([[self::CP1, $this->min(40)], [self::FINISH, $this->min(120)]], finishCutoff: $this->min(120));

        $this->assertSame('finisher', $r['status']);
    }

    public function test_missing_mandatory_checkpoint_is_dq_flagged_for_review(): void
    {
        $r = $this->eval([[self::START, self::GUN], [self::FINISH, $this->min(50)]]);

        $this->assertSame('dq', $r['status']);
        $this->assertTrue($r['needs_review']);
        $this->assertSame('Tidak tercatat di pos wajib: CP1', $r['review_note']);
        $this->assertSame(3_000_000, $r['gun_time_ms']); // waktu tetap tersimpan untuk review
    }

    public function test_missing_optional_water_station_is_still_finisher(): void
    {
        $this->assertSame('finisher', $this->eval([[self::CP1, $this->min(40)], [self::FINISH, $this->min(70)]])['status']);
    }

    public function test_dnf_takes_precedence_over_dq(): void
    {
        $this->assertSame('dnf', $this->eval([[self::START, self::GUN]])['status']);
    }

    public function test_first_finish_scan_is_used(): void
    {
        $r = $this->eval([
            [self::CP1, $this->min(40)],
            [self::FINISH, $this->min(72)],
            [self::FINISH, $this->min(70)],
            [self::FINISH, $this->min(75)],
        ]);

        $this->assertSame($this->min(70), $r['finish_ms']);
    }

    public function test_scan_order_does_not_matter(): void
    {
        $scans = [
            [self::START, self::GUN + 2_000], [self::START, self::GUN - 300_000],
            [self::CP1, $this->min(41)], [self::CP1, $this->min(40)],
            [self::FINISH, $this->min(70)], [self::FINISH, $this->min(71)],
        ];
        $expected = $this->eval($scans);

        for ($i = 0; $i < 10; $i++) {
            shuffle($scans);
            $this->assertSame($expected, $this->eval($scans));
        }
    }

    public function test_checkpoint_times_out_of_route_order_are_not_dq(): void
    {
        // Aturan "urutan terbalik → DQ" dihapus: jam HP meleset tidak boleh men-DQ pelari.
        $r = $this->eval([[self::CP1, $this->min(60)], [self::WS, $this->min(50)], [self::FINISH, $this->min(70)]]);

        $this->assertSame('finisher', $r['status']);
        $this->assertFalse($r['needs_review']);
    }

    public function test_start_scanned_only_before_gun_uses_gun(): void
    {
        $r = $this->eval([[self::START, self::GUN - 120_000], [self::CP1, $this->min(40)], [self::FINISH, $this->min(70)]]);

        $this->assertSame(self::GUN, $r['start_ms']);
        $this->assertSame($r['gun_time_ms'], $r['net_time_ms']);
    }

    public function test_start_uses_first_scan_after_gun(): void
    {
        $r = $this->eval([
            [self::START, self::GUN - 60_000],
            [self::START, self::GUN + 90_000],
            [self::START, self::GUN + 30_000],
            [self::CP1, $this->min(40)],
            [self::FINISH, $this->min(70)],
        ]);

        $this->assertSame(self::GUN + 30_000, $r['start_ms']);
    }

    public function test_without_start_scan_net_time_is_empty(): void
    {
        $r = $this->eval([[self::CP1, $this->min(40)], [self::FINISH, $this->min(70)]]);

        $this->assertSame('finisher', $r['status']);
        $this->assertSame(4_200_000, $r['gun_time_ms']);
        $this->assertNull($r['net_time_ms']);
    }

    public function test_finisher_without_gun_time_needs_review(): void
    {
        $r = $this->eval([[self::START, self::GUN], [self::CP1, $this->min(40)], [self::FINISH, $this->min(70)]], gun: null);

        $this->assertSame('finisher', $r['status']);
        $this->assertNull($r['gun_time_ms']);
        $this->assertSame(4_200_000, $r['net_time_ms']);
        $this->assertTrue($r['needs_review']);
        $this->assertSame('Gun time kategori belum ada', $r['review_note']);
    }

    public function test_checkpoint_over_cutoff_only_flags_review(): void
    {
        $r = $this->eval([[self::CP1, $this->min(95)], [self::FINISH, $this->min(130)]], cp1Cutoff: $this->min(90));

        $this->assertSame('finisher', $r['status']);
        $this->assertTrue($r['needs_review']);
        $this->assertSame('Melewati cut-off di: CP1', $r['review_note']);
        $this->assertTrue($r['checkpoints'][1]['over_cutoff']);
    }

    public function test_finish_before_gun_needs_review(): void
    {
        $r = $this->eval([[self::CP1, self::GUN - 600_000], [self::FINISH, self::GUN - 60_000]]);

        $this->assertTrue($r['needs_review']);
        $this->assertSame(0, $r['gun_time_ms']);
    }

    public function test_admin_status_adjustment_wins_and_clears_review(): void
    {
        $r = $this->eval([[self::START, self::GUN], [self::FINISH, $this->min(50)]], adj: ['status' => 'finisher']);

        $this->assertSame('finisher', $r['status']);
        $this->assertFalse($r['needs_review']);
    }

    public function test_admin_finish_time_adjustment(): void
    {
        $r = $this->eval([[self::CP1, $this->min(40)]], adj: ['finish_time' => $this->min(80)]);

        $this->assertSame('finisher', $r['status']);
        $this->assertSame(4_800_000, $r['gun_time_ms']);
    }

    public function test_admin_can_remove_finish_time(): void
    {
        $r = $this->eval([[self::CP1, $this->min(40)], [self::FINISH, $this->min(70)]], adj: ['finish_time' => null]);

        $this->assertSame('dnf', $r['status']);
    }

    public function test_rank_by_gun_time_then_finish_then_bib_per_gender(): void
    {
        $rows = [
            10 => ['status' => 'finisher', 'gun_time_ms' => 3_000_000, 'finish_ms' => 2, 'gender' => 'M', 'bib' => 7003],
            11 => ['status' => 'finisher', 'gun_time_ms' => 2_500_000, 'finish_ms' => 1, 'gender' => 'F', 'bib' => 7004],
            12 => ['status' => 'finisher', 'gun_time_ms' => 3_000_000, 'finish_ms' => 2, 'gender' => 'M', 'bib' => 7001],
            13 => ['status' => 'finisher', 'gun_time_ms' => 2_800_000, 'finish_ms' => 1, 'gender' => 'M', 'bib' => 7009],
            14 => ['status' => 'dq', 'gun_time_ms' => 1_000_000, 'finish_ms' => 1, 'gender' => 'M', 'bib' => 7010],
            15 => ['status' => 'dnf', 'gun_time_ms' => null, 'finish_ms' => null, 'gender' => 'F', 'bib' => 7011],
            16 => ['status' => 'finisher', 'gun_time_ms' => null, 'finish_ms' => 1, 'gender' => 'F', 'bib' => 7012], // gun time belum ada
            17 => ['status' => 'finisher', 'gun_time_ms' => 3_500_000, 'finish_ms' => 3, 'gender' => null, 'bib' => 7013],
        ];

        $this->assertSame([
            11 => ['overall' => 1, 'gender' => 1],
            13 => ['overall' => 2, 'gender' => 1],
            12 => ['overall' => 3, 'gender' => 2],
            10 => ['overall' => 4, 'gender' => 3],
            17 => ['overall' => 5, 'gender' => null],
        ], ResultCalculator::rank($rows));
    }

    public function test_point_passed_twice_records_each_visit(): void
    {
        // 15K: Pertigaan dilewati dua kali (WS1 km 7.59, WS2 km 12.3), Puncak di antaranya.
        $route = [
            ['id' => self::CP1, 'code' => 'CP1', 'name' => 'Air Terjun', 'type' => 'checkpoint', 'sequence' => 1, 'mandatory' => true, 'cutoff_ms' => null,
                'visits' => [['code' => 'CP1', 'km' => 5.6, 'cutoff_ms' => null]]],
            ['id' => self::WS, 'code' => 'WS1', 'name' => 'Pertigaan', 'type' => 'water_station', 'sequence' => 2, 'mandatory' => false, 'cutoff_ms' => null,
                'visits' => [['code' => 'WS1', 'km' => 7.59, 'cutoff_ms' => null], ['code' => 'WS2', 'km' => 12.3, 'cutoff_ms' => $this->min(150)]]],
            ['id' => self::START, 'code' => 'CP2', 'name' => 'Puncak', 'type' => 'checkpoint', 'sequence' => 3, 'mandatory' => true, 'cutoff_ms' => null,
                'visits' => [['code' => 'CP2', 'km' => 11.34, 'cutoff_ms' => null]]],
            ['id' => self::FINISH, 'code' => 'FINISH', 'name' => 'Finish', 'type' => 'finish', 'sequence' => 4, 'mandatory' => true, 'cutoff_ms' => null,
                'visits' => [['code' => 'FINISH', 'km' => 14.92, 'cutoff_ms' => null]]],
        ];
        $r = ResultCalculator::evaluate($route, self::GUN, [
            [self::CP1, $this->min(50)],
            [self::WS, $this->min(70)], [self::WS, $this->min(72)],   // dobel di WS1 (2 menit) → 1 lewat
            [self::START, $this->min(110)],
            [self::WS, $this->min(160)],                              // lewat ke-2 → WS2 (lewat cut-off 150)
            [self::FINISH, $this->min(185)],
        ]);

        $this->assertSame(['CP1', 'WS1', 'CP2', 'WS2', 'FINISH'], array_column($r['checkpoints'], 'code'));
        $byCode = array_column($r['checkpoints'], null, 'code');
        $this->assertSame($this->min(70), $byCode['WS1']['time_ms']);
        $this->assertSame($this->min(160), $byCode['WS2']['time_ms']);
        $this->assertSame(2, $byCode['WS2']['visit']);
        $this->assertTrue($byCode['WS2']['over_cutoff']);
        $this->assertSame('finisher', $r['status']);
        $this->assertSame('Melewati cut-off di: WS2', $r['review_note']);
    }

    public function test_missing_second_visit_of_optional_ws_is_not_dq(): void
    {
        $route = [
            ['id' => self::WS, 'code' => 'WS1', 'name' => 'Pertigaan', 'type' => 'water_station', 'sequence' => 1, 'mandatory' => false, 'cutoff_ms' => null,
                'visits' => [['code' => 'WS1', 'km' => 7.59, 'cutoff_ms' => null], ['code' => 'WS2', 'km' => 12.3, 'cutoff_ms' => null]]],
            ['id' => self::FINISH, 'code' => 'FINISH', 'name' => 'Finish', 'type' => 'finish', 'sequence' => 2, 'mandatory' => true, 'cutoff_ms' => null,
                'visits' => [['code' => 'FINISH', 'km' => 14.92, 'cutoff_ms' => null]]],
        ];
        $r = ResultCalculator::evaluate($route, self::GUN, [[self::WS, $this->min(70)], [self::FINISH, $this->min(185)]]);

        $this->assertSame('finisher', $r['status']);
        $this->assertNull(array_column($r['checkpoints'], null, 'code')['WS2']['time_ms']);
    }
}
