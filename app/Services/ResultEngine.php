<?php

namespace App\Services;

use App\Models\Event;
use App\Models\GtrCategory;
use App\Models\GtrRegistration;
use App\Models\GtrResult;
use App\Models\GtrTimingPoint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Mesin penghitung hasil GTR.
 *
 * Aturan (lihat FASE 4 spesifikasi):
 *  - start_time  = scan di titik type=start; fallback category.gun_start lalu event.default_gun_start.
 *  - finish_time = scan PALING AWAL di titik type=finish.
 *  - net_time = finish - start ; gun_time = finish - gun_start.
 *  - Wajib lewat SEMUA titik is_mandatory kategori, urut sesuai sequence, sebelum cutoff:
 *      * tanpa scan apa pun                 -> DNS
 *      * titik wajib (tengah) bolong / urutan kebalik -> DQ
 *      * ada start, tanpa finish            -> DNF
 *      * finish setelah cut_off             -> DNF (over-COT)
 *      * selain itu                         -> FINISHER
 *  - Start boleh tanpa scan (otomatis dari gun_start). Finish bolong = DNF, bukan DQ.
 */
class ResultEngine
{
    /** @return array<string,int> ringkasan jumlah per status */
    public function compute(Event $event): array
    {
        $event->loadMissing('timingPoints');
        $timingPointIds = $event->timingPoints->pluck('id');

        if ($timingPointIds->isEmpty()) {
            return ['finisher' => 0, 'dnf' => 0, 'dq' => 0, 'dns' => 0];
        }

        $defaultGunStart = $event->default_gun_start;

        // Kategori yang ikut event ini = punya pivot ke salah satu titik event.
        $categories = GtrCategory::whereHas('timingPoints', fn ($q) => $q->whereIn('gtr_timing_points.id', $timingPointIds))
            ->with(['timingPoints' => fn ($q) => $q->whereIn('gtr_timing_points.id', $timingPointIds)])
            ->get();

        $computed = collect(); // GtrResult terhitung (untuk ranking)

        DB::transaction(function () use ($categories, $defaultGunStart, &$computed) {
            foreach ($categories as $category) {
                $gunStart = $category->gun_start ?? $defaultGunStart;
                $pivots = $category->timingPoints->sortBy('pivot.sequence')->values();

                $startPoint = $pivots->firstWhere('type', GtrTimingPoint::TYPE_START);
                $finishPoint = $pivots->firstWhere('type', GtrTimingPoint::TYPE_FINISH);
                $mandatoryMiddle = $pivots->filter(
                    fn ($p) => $p->pivot->is_mandatory
                        && ! in_array($p->type, [GtrTimingPoint::TYPE_START, GtrTimingPoint::TYPE_FINISH], true)
                );

                $registrations = $category->registrations()->get();

                foreach ($registrations as $reg) {
                    $result = $this->computeFor($reg, $pivots, $startPoint, $finishPoint, $mandatoryMiddle, $gunStart);
                    $computed->push([
                        'result' => $result,
                        'category_id' => $reg->gtr_category_id,
                        'gender' => $reg->genderCode(),
                    ]);
                }
            }
        });

        $this->assignRanks($computed);

        $countByStatus = fn (string $s) => $computed->filter(fn ($row) => $row['result']->status === $s)->count();

        return [
            'finisher' => $countByStatus(GtrResult::STATUS_FINISHER),
            'dnf' => $countByStatus(GtrResult::STATUS_DNF),
            'dq' => $countByStatus(GtrResult::STATUS_DQ),
            'dns' => $countByStatus(GtrResult::STATUS_DNS),
        ];
    }

    /**
     * Hitung satu peserta dan simpan ke gtr_results + sinkron race_status.
     */
    private function computeFor(
        GtrRegistration $reg,
        Collection $pivots,
        ?GtrTimingPoint $startPoint,
        ?GtrTimingPoint $finishPoint,
        Collection $mandatoryMiddle,
        ?Carbon $gunStart,
    ): GtrResult {
        // Scan paling awal per titik: tpId => Carbon.
        $earliest = $reg->scanLogs()
            ->whereIn('gtr_timing_point_id', $pivots->pluck('id'))
            ->get(['gtr_timing_point_id', 'scanned_at'])
            ->groupBy('gtr_timing_point_id')
            ->map(fn ($g) => $g->min('scanned_at'));

        $status = GtrResult::STATUS_FINISHER;
        $netSeconds = null;
        $gunSeconds = null;

        if ($earliest->isEmpty()) {
            $status = GtrResult::STATUS_DNS;
        } else {
            // DQ: titik wajib (tengah) bolong.
            $missingMandatory = $mandatoryMiddle->first(fn ($p) => ! $earliest->has($p->id));

            // DQ: urutan kebalik — titik yang discan, diurut sequence, waktunya harus naik.
            $orderViolation = false;
            $prev = null;
            foreach ($pivots as $p) {
                if (! $earliest->has($p->id)) {
                    continue;
                }
                $t = $earliest->get($p->id);
                if ($prev !== null && $t->lt($prev)) {
                    $orderViolation = true;
                    break;
                }
                $prev = $t;
            }

            if ($missingMandatory || $orderViolation) {
                $status = GtrResult::STATUS_DQ;
            } else {
                $startTime = ($startPoint ? $earliest->get($startPoint->id) : null) ?? $gunStart;
                $finishTime = $finishPoint ? $earliest->get($finishPoint->id) : null;

                if (! $finishTime) {
                    $status = GtrResult::STATUS_DNF; // ada start, tanpa finish
                } else {
                    $cutoff = $finishPoint?->pivot->cutoff_at ?? $reg->category?->cut_off_at;
                    if ($cutoff && $finishTime->gt($cutoff)) {
                        $status = GtrResult::STATUS_DNF; // over-COT
                    } else {
                        $status = GtrResult::STATUS_FINISHER;
                        if ($startTime) {
                            $netSeconds = max(0, $startTime->diffInSeconds($finishTime, false));
                        }
                        if ($gunStart) {
                            $gunSeconds = max(0, $gunStart->diffInSeconds($finishTime, false));
                        }
                    }
                }
            }
        }

        $result = GtrResult::updateOrCreate(
            ['gtr_registration_id' => $reg->id],
            [
                'gun_time_seconds' => $gunSeconds,
                'net_time_seconds' => $netSeconds,
                'rank_overall' => null,
                'rank_category' => null,
                'rank_gender' => null,
                'status' => $status,
                'computed_at' => now(),
            ],
        );

        // Sinkron status lomba ke pendaftaran.
        $reg->forceFill(['race_status' => $status])->save();

        return $result;
    }

    /**
     * Peringkat overall / kategori / gender di antara finisher (urut net_time naik).
     *
     * @param  Collection<int,array{result:GtrResult,category_id:int,gender:?string}>  $rows
     */
    private function assignRanks(Collection $rows): void
    {
        $finishers = $rows
            ->filter(fn ($row) => $row['result']->status === GtrResult::STATUS_FINISHER
                && $row['result']->net_time_seconds !== null)
            ->sortBy(fn ($row) => $row['result']->net_time_seconds)
            ->values();

        $overall = 0;
        $byCategory = [];
        $byGender = [];

        foreach ($finishers as $row) {
            $r = $row['result'];
            $cat = $row['category_id'];
            $gen = $row['gender'] ?? '?';

            $r->rank_overall = ++$overall;
            $r->rank_category = $byCategory[$cat] = ($byCategory[$cat] ?? 0) + 1;
            $r->rank_gender = $byGender[$gen] = ($byGender[$gen] ?? 0) + 1;
            $r->saveQuietly();
        }
    }
}
