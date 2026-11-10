<?php

namespace App\Services;

use App\Casts\DateTimeMs;
use App\Models\GtrCategory;
use App\Models\GtrRegistration;
use App\Models\GtrResult;
use App\Models\GtrResultAdjustment;
use App\Models\GtrScanLog;
use App\Models\GtrSetting;
use App\Models\GtrTimingPoint;
use App\Support\Passes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Penghitung hasil GTR per kategori. Semua waktu dalam epoch milidetik.
 *
 * Aturan (lihat evaluate()):
 *  - Tidak ada catatan waktu sama sekali              → DNS
 *  - Ada catatan waktu tapi tidak finish               → DNF
 *  - Finish melewati cut-off finish                    → DNF
 *  - Finish tapi tidak tercatat di CP wajib            → DQ + needs_review (admin memutuskan)
 *  - Selain itu                                        → FINISHER
 *  - Melewati cut-off pos tengah → hanya needs_review (status tidak diubah otomatis).
 *
 * Waktu:
 *  - finish = scan sah PERTAMA di FINISH (waktu terkoreksi).
 *  - gun time = finish − gun time resmi kategori (gun_fired_at, fallback jadwal gun_start). Resmi.
 *  - net time = finish − start; start = scan START sah pertama pada/setelah gun. Bila hanya
 *    ada scan START sebelum gun, start = gun. Tanpa scan START → net time kosong.
 *  - Ranking per kategori (overall & gender) dari FINISHER berdasarkan gun time,
 *    seri → finish lebih awal → nomor BIB.
 *  - Koreksi admin aktif (gtr_result_adjustments) diterapkan setiap hitung ulang.
 *
 * Status di gtr_results selalu status AKHIR menurut aturan di atas (selama lomba, pelari di
 * lintasan terhitung DNF). gtr_registrations.race_status hanya disinkron setelah
 * gtr_settings.results_published = true; saat dinyalakan, semua hasil dihitung ulang (GtrSetting).
 */
class ResultCalculator
{
    private const EMPTY = ['finisher' => 0, 'dnf' => 0, 'dq' => 0, 'dns' => 0, 'review' => 0];

    /** @return array{finisher:int,dnf:int,dq:int,dns:int,review:int} */
    public function recomputeAll(): array
    {
        // Peserta yang tak lagi ikut timing (batal / BIB dicabut) → hasil lamanya dihapus.
        GtrResult::whereNotIn('gtr_registration_id', GtrRegistration::query()->raceEligible()->select('id'))->delete();

        $sum = self::EMPTY;
        foreach (GtrCategory::orderBy('sort_order')->orderBy('id')->pluck('id') as $id) {
            foreach ($this->recomputeCategory($id) as $k => $v) {
                $sum[$k] += $v;
            }
        }

        return $sum;
    }

    /** @return array{finisher:int,dnf:int,dq:int,dns:int,review:int} */
    public function recomputeCategory(GtrCategory|int $category): array
    {
        $category = GtrCategory::find($category instanceof GtrCategory ? $category->id : $category);
        if (! $category) {
            return self::EMPTY;
        }

        $route = $this->route($category);
        $gunMs = DateTimeMs::toMs($category->officialGunTime());

        $regs = GtrRegistration::query()->raceEligible()
            ->where('gtr_category_id', $category->id)
            ->get(['id', 'bib_number', 'gender', 'gtr_category_id']);
        $ids = $regs->pluck('id')->all();

        $scans = GtrScanLog::query()->valid()
            ->whereIn('gtr_registration_id', $ids)
            ->whereIn('gtr_timing_point_id', array_column($route, 'id'))
            ->get(['gtr_registration_id', 'gtr_timing_point_id', 'scanned_at'])
            ->groupBy('gtr_registration_id')
            ->map(fn (Collection $g) => $g->map(fn (GtrScanLog $s) => [$s->gtr_timing_point_id, DateTimeMs::toMs($s->scanned_at)])->all());

        $adjustments = $this->adjustments($ids);

        $rows = [];
        foreach ($regs as $reg) {
            $rows[$reg->id] = self::evaluate($route, $gunMs, $scans->get($reg->id, []), $adjustments[$reg->id] ?? [])
                + ['gender' => $reg->genderCode(), 'bib' => (int) $reg->bib_number];
        }
        $ranks = self::rank($rows);

        DB::transaction(function () use ($category, $rows, $ranks, $ids) {
            $now = now();
            foreach ($rows as $regId => $r) {
                GtrResult::updateOrCreate(['gtr_registration_id' => $regId], [
                    'gtr_category_id' => $category->id,
                    'status' => $r['status'],
                    'start_time' => $r['start_ms'],
                    'finish_time' => $r['finish_ms'],
                    'gun_time_ms' => $r['gun_time_ms'],
                    'net_time_ms' => $r['net_time_ms'],
                    'gun_time_seconds' => $r['gun_time_ms'] === null ? null : intdiv($r['gun_time_ms'], 1000),
                    'net_time_seconds' => $r['net_time_ms'] === null ? null : intdiv($r['net_time_ms'], 1000),
                    'rank_overall' => $ranks[$regId]['overall'] ?? null,
                    'rank_category' => $ranks[$regId]['overall'] ?? null,
                    'rank_gender' => $ranks[$regId]['gender'] ?? null,
                    'checkpoints_json' => $r['checkpoints'],
                    'needs_review' => $r['needs_review'],
                    'review_note' => $r['review_note'],
                    'computed_at' => $now,
                ]);
            }

            GtrResult::where('gtr_category_id', $category->id)->whereNotIn('gtr_registration_id', $ids)->delete();

            // Status lomba di pendaftaran (dibaca /lacak) baru disinkron setelah hasil dipublikasikan:
            // selama lomba, pelari di lintasan tidak boleh tampil DNF/DNS. Tanpa event model →
            // tidak memicu email/BIB.
            if (GtrSetting::first()?->results_published) {
                foreach (collect($rows)->groupBy('status', true) as $status => $group) {
                    GtrRegistration::whereIn('id', $group->keys())->where('race_status', '!=', $status)->update(['race_status' => $status]);
                }
            }
        });

        $summary = self::EMPTY;
        foreach ($rows as $r) {
            $summary[$r['status']]++;
            $summary['review'] += (int) $r['needs_review'];
        }

        return $summary;
    }

    /**
     * Inti perhitungan satu peserta (fungsi murni, tanpa database).
     *
     * @param  list<array{id:int,code:string,name:string,type:string,sequence:int,mandatory:bool,cutoff_ms:?int}>  $route  urut sequence
     * @param  list<array{0:int,1:int}>  $scans  [timing_point_id, corrected_ms] — hanya scan sah
     * @param  array{status?:string,start_time?:?int,finish_time?:?int}  $adj  koreksi admin aktif
     * @return array{status:string,start_ms:?int,finish_ms:?int,gun_time_ms:?int,net_time_ms:?int,checkpoints:list<array>,needs_review:bool,review_note:?string}
     */
    public static function evaluate(array $route, ?int $gunMs, array $scans, array $adj = []): array
    {
        $times = [];
        foreach ($scans as [$pointId, $ms]) {
            $times[$pointId][] = $ms;
        }
        foreach ($times as &$list) {
            sort($list);
        }
        unset($list);

        $start = collect($route)->firstWhere('type', GtrTimingPoint::TYPE_START);
        $finish = collect($route)->firstWhere('type', GtrTimingPoint::TYPE_FINISH);

        $startMs = array_key_exists('start_time', $adj)
            ? $adj['start_time']
            : self::pickStart($start ? ($times[$start['id']] ?? []) : [], $gunMs);
        $finishMs = array_key_exists('finish_time', $adj)
            ? $adj['finish_time']
            : ($finish ? ($times[$finish['id']][0] ?? null) : null);

        $checkpoints = [];
        $missing = [];
        $late = [];
        foreach ($route as $p) {
            $middle = ! in_array($p['type'], [GtrTimingPoint::TYPE_START, GtrTimingPoint::TYPE_FINISH], true);
            // Pos yang dilewati >1 kali oleh kategori ini (mis. 15K: WS1 & WS2 di Pertigaan):
            // scan dikelompokkan jadi "lewat" (jarak ≥ 10 menit), lewat ke-k = kunjungan ke-k.
            $visits = $p['visits'] ?? [['code' => $p['code'], 'km' => null, 'cutoff_ms' => $p['cutoff_ms']]];
            $passes = $middle ? Passes::cluster($times[$p['id']] ?? []) : [];

            if ($middle && $p['mandatory'] && $passes === []) {
                $missing[] = $p['code'];
            }

            foreach ($visits as $k => $v) {
                $t = match ($p['type']) {
                    GtrTimingPoint::TYPE_START => $startMs,
                    GtrTimingPoint::TYPE_FINISH => $finishMs,
                    default => $passes[$k] ?? null,
                };
                $cutoff = $v['cutoff_ms'] ?? ($k === 0 ? $p['cutoff_ms'] : null);
                $over = $t !== null && $cutoff !== null && $t > $cutoff;
                if ($middle && $over) {
                    $late[] = $v['code'];
                }

                $checkpoints[] = [
                    'point_id' => $p['id'],
                    'code' => $v['code'],
                    'name' => $p['name'],
                    'type' => $p['type'],
                    'sequence' => $p['sequence'],
                    'visit' => $k + 1,
                    'km' => $v['km'],
                    'mandatory' => $p['mandatory'] && $k === 0,
                    'time_ms' => $t,
                    'elapsed_ms' => $t !== null && $gunMs !== null ? $t - $gunMs : null,
                    'cutoff_ms' => $cutoff,
                    'over_cutoff' => $over,
                ];
            }
        }
        // Urut posisi di rute (KM) supaya WS2 muncul setelah CP sesudah WS1.
        usort($checkpoints, fn ($a, $b) => ($a['km'] !== null && $b['km'] !== null)
            ? $a['km'] <=> $b['km']
            : [$a['sequence'], $a['visit']] <=> [$b['sequence'], $b['visit']]);

        $hasTime = $times !== [] || $startMs !== null || $finishMs !== null;
        $finishCutoff = $finish['cutoff_ms'] ?? null;
        $notes = [];
        $review = false;

        if (! $hasTime) {
            $status = GtrResult::STATUS_DNS;
        } elseif ($finishMs === null) {
            $status = GtrResult::STATUS_DNF;
        } elseif ($finishCutoff !== null && $finishMs > $finishCutoff) {
            $status = GtrResult::STATUS_DNF;
            $notes[] = 'Finish melewati cut-off';
        } elseif ($missing) {
            $status = GtrResult::STATUS_DQ;
            $review = true;
            $notes[] = 'Tidak tercatat di pos wajib: '.implode(', ', $missing);
        } else {
            $status = GtrResult::STATUS_FINISHER;
        }

        if ($late && $status !== GtrResult::STATUS_DNS) {
            $review = true;
            $notes[] = 'Melewati cut-off di: '.implode(', ', $late);
        }
        if ($status === GtrResult::STATUS_FINISHER && $gunMs === null) {
            $review = true;
            $notes[] = 'Gun time kategori belum ada';
        }
        if ($finishMs !== null && $gunMs !== null && $finishMs < $gunMs) {
            $review = true;
            $notes[] = 'Waktu finish sebelum start';
        }

        if (isset($adj['status'])) {
            $status = $adj['status'];
            $review = false; // sudah diputuskan admin
            $notes[] = 'Status dikoreksi admin';
        }

        return [
            'status' => $status,
            'start_ms' => $startMs,
            'finish_ms' => $finishMs,
            'gun_time_ms' => $finishMs !== null && $gunMs !== null ? max(0, $finishMs - $gunMs) : null,
            'net_time_ms' => $finishMs !== null && $startMs !== null ? max(0, $finishMs - $startMs) : null,
            'checkpoints' => $checkpoints,
            'needs_review' => $review,
            'review_note' => $notes ? implode(' · ', $notes) : null,
        ];
    }

    /** Scan START pertama pada/setelah gun; hanya sebelum gun → gun; tanpa gun → scan pertama. */
    public static function pickStart(array $sortedMs, ?int $gunMs): ?int
    {
        if ($sortedMs === []) {
            return null;
        }
        if ($gunMs === null) {
            return $sortedMs[0];
        }
        foreach ($sortedMs as $ms) {
            if ($ms >= $gunMs) {
                return $ms;
            }
        }

        return $gunMs;
    }

    /**
     * Peringkat FINISHER satu kategori: overall & per gender, urut gun time → finish → BIB.
     *
     * @param  array<int,array{status:string,gun_time_ms:?int,finish_ms:?int,gender:?string,bib:int}>  $rows  keyed by registration id
     * @return array<int,array{overall:int,gender:?int}>
     */
    public static function rank(array $rows): array
    {
        $finishers = collect($rows)
            ->filter(fn ($r) => $r['status'] === GtrResult::STATUS_FINISHER && $r['gun_time_ms'] !== null)
            ->sortBy([['gun_time_ms', 'asc'], ['finish_ms', 'asc'], ['bib', 'asc']]);

        $ranks = [];
        $overall = 0;
        $byGender = [];
        foreach ($finishers as $regId => $r) {
            $gender = $r['gender'];
            $ranks[$regId] = [
                'overall' => ++$overall,
                'gender' => $gender ? ($byGender[$gender] = ($byGender[$gender] ?? 0) + 1) : null,
            ];
        }

        return $ranks;
    }

    /** Rute kategori: titik timing urut sequence + cut-off (ms). Cut-off finish fallback ke cut_off_at kategori. */
    private function route(GtrCategory $category): array
    {
        $tz = config('app.timezone');

        return $category->timingPoints()->get()->map(function (GtrTimingPoint $p) use ($category, $tz) {
            $cutoff = $p->pivot->cutoff_at ? Carbon::parse($p->pivot->cutoff_at, $tz) : null;
            if (! $cutoff && $p->type === GtrTimingPoint::TYPE_FINISH && $category->cut_off_at) {
                $cutoff = $category->cut_off_at;
            }

            $cutoffMs = DateTimeMs::toMs($cutoff);
            $visits = array_map(fn ($v) => [
                'code' => $v['code'],
                'km' => $v['km'],
                'cutoff_ms' => $v['cutoff'] ? DateTimeMs::toMs(GtrTimingSync::raceTime($v['cutoff'])) : null,
            ], Passes::visits($p->pivot->visits, $p->code, $p->pivot->km));
            if ($visits[0]['cutoff_ms'] === null) {
                $visits[0]['cutoff_ms'] = $cutoffMs; // cut-off finish / pivot lama
            }

            return [
                'id' => $p->id,
                'code' => $p->code,
                'name' => $p->name,
                'type' => $p->type,
                'sequence' => (int) $p->pivot->sequence,
                'mandatory' => (bool) $p->pivot->is_mandatory,
                'cutoff_ms' => $cutoffMs,
                'visits' => $visits,
            ];
        })->values()->all();
    }

    /**
     * Koreksi aktif per peserta; koreksi terbaru per field menang.
     *
     * @return array<int,array{status?:string,start_time?:?int,finish_time?:?int}>
     */
    private function adjustments(array $registrationIds): array
    {
        $statuses = [GtrResult::STATUS_FINISHER, GtrResult::STATUS_DNF, GtrResult::STATUS_DQ, GtrResult::STATUS_DNS];
        $out = [];

        $rows = GtrResultAdjustment::where('is_active', true)->whereIn('gtr_registration_id', $registrationIds)->orderBy('id')->get();
        foreach ($rows as $a) {
            $v = $a->new_value;
            match ($a->field) {
                GtrResultAdjustment::FIELD_STATUS => in_array($v, $statuses, true) ? $out[$a->gtr_registration_id]['status'] = $v : null,
                GtrResultAdjustment::FIELD_START_TIME,
                GtrResultAdjustment::FIELD_FINISH_TIME => $out[$a->gtr_registration_id][$a->field] = self::parseMs($v),
                default => null,
            };
        }

        return $out;
    }

    /** Nilai koreksi waktu: epoch ms atau "Y-m-d H:i:s(.v)" zona app; kosong = hapus waktu. */
    public static function parseMs(?string $value): ?int
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return ctype_digit($value) ? (int) $value : DateTimeMs::toMs(DateTimeMs::toCarbon($value));
    }
}
