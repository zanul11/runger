<?php

namespace App\Services;

use App\Http\Controllers\RunnerDashboardController;
use App\Models\Event;
use App\Models\GtrRegistration;
use App\Models\GtrTimingPoint;
use Illuminate\Support\Carbon;

/**
 * Live tracking peserta GTR dari scan log timing point:
 * titik yang sudah dilewati, perkiraan posisi sekarang, dan selisih terhadap race plan.
 */
class LiveTracker
{
    public function build(GtrRegistration $reg): array
    {
        $reg->loadMissing('category');
        $cat = $reg->category;
        $course = $cat->courseSegments();
        $totalKm = (float) ($course['total_km'] ?? $cat->total_km ?? 0);
        $coursePoints = $cat->coursePoints()->keyBy('code');

        // titik timing kategori (urut sequence) + posisi KM di rute
        $points = $cat->timingPoints()->get()->map(function (GtrTimingPoint $tp) use ($totalKm, $coursePoints) {
            $km = $tp->pivot->km !== null ? (float) $tp->pivot->km : match ($tp->type) {
                GtrTimingPoint::TYPE_START => 0.0,
                GtrTimingPoint::TYPE_FINISH => $totalKm,
                default => $coursePoints->get($tp->code)['km'] ?? null,
            };

            return [
                'id' => $tp->id,
                'code' => $tp->code,
                'name' => $tp->name,
                'type' => $tp->type,
                'km' => $km,
                'cutoff_at' => $tp->pivot->cutoff_at,
            ];
        })->values();

        // scan paling awal per titik
        $earliest = $reg->scanLogs()
            ->whereIn('gtr_timing_point_id', $points->pluck('id'))
            ->get(['gtr_timing_point_id', 'scanned_at'])
            ->groupBy('gtr_timing_point_id')
            ->map(fn ($g) => $g->min('scanned_at'));

        $startPoint = $points->firstWhere('type', GtrTimingPoint::TYPE_START);
        $finishPoint = $points->firstWhere('type', GtrTimingPoint::TYPE_FINISH);
        $startAt = ($startPoint ? $earliest->get($startPoint['id']) : null)
            ?? $cat->gun_start
            ?? Event::query()->value('default_gun_start');
        $startAt = $startAt ? Carbon::parse($startAt) : null;
        $finishAt = $finishPoint ? $earliest->get($finishPoint['id']) : null;

        // race plan: target tersimpan atau bawaan
        $target = $reg->target_finish_minutes ?: RunnerDashboardController::defaultTarget($cat);
        $planAt = $this->planElapsedFn($course['splits'] ?? [], $target);

        $passes = $points->map(function ($p) use ($earliest, $startAt, $planAt) {
            $at = $earliest->get($p['id']);
            $elapsed = $at && $startAt ? max(0, $startAt->diffInSeconds($at, false)) : null;
            $plan = $p['km'] !== null && $planAt ? (int) round($planAt($p['km']) * 60) : null;

            return $p + [
                'passed' => (bool) $at,
                'at' => $at ? Carbon::parse($at)->format('H:i:s') : null,
                'elapsed' => $elapsed !== null ? (int) $elapsed : null,
                'plan_elapsed' => $plan,
                'delta' => $elapsed !== null && $plan !== null && $p['type'] !== GtrTimingPoint::TYPE_START ? (int) $elapsed - $plan : null,
                'cutoff' => $p['cutoff_at'] ? Carbon::parse($p['cutoff_at'])->format('H:i') : null,
            ];
        });

        $last = $passes->filter(fn ($p) => $p['passed'] && $p['km'] !== null)->sortBy('km')->last();
        $status = match (true) {
            in_array($reg->race_status, [GtrRegistration::RACE_DNF, GtrRegistration::RACE_DQ], true) => $reg->race_status,
            $reg->race_status === GtrRegistration::RACE_DNS && $earliest->isEmpty() => GtrRegistration::RACE_DNS,
            (bool) $finishAt => 'finished',
            (bool) $last => 'on_course',
            default => 'not_started',
        };

        // perkiraan posisi: pace aktual relatif terhadap plan sampai titik terakhir, diproyeksikan ke sekarang
        $estimate = null;
        if ($status === 'on_course' && $startAt && $planAt) {
            $now = max(0, $startAt->diffInSeconds(now(), false)) / 60;
            $ratio = ($last['plan_elapsed'] ?? 0) > 60 && $last['elapsed'] ? $last['elapsed'] / $last['plan_elapsed'] : 1.0;
            $next = $passes->filter(fn ($p) => ! $p['passed'] && $p['km'] !== null && $p['km'] > $last['km'])->sortBy('km')->first();
            $cap = $next ? max($last['km'], $next['km'] - 0.1) : $totalKm;
            $km = $last['km'];
            for ($k = $last['km']; $k <= $cap; $k += 0.05) {
                if ($planAt($k) * $ratio > $now) {
                    break;
                }
                $km = $k;
            }
            $estimate = ['km' => round($km, 2), 'next' => $next['code'] ?? null];
        }

        return [
            'bib' => $reg->bib_number,
            'name' => $reg->full_name,
            'club' => $reg->club,
            'category' => ['name' => $cat->name, 'distance' => $cat->distance, 'color' => $cat->color, 'slug' => $cat->slug],
            'status' => $status,
            'start_at' => $startAt?->format('H:i:s'),
            'start_ts' => $startAt?->getTimestampMs(),
            'now_ts' => now()->getTimestampMs(),
            'finish_at' => $finishAt ? Carbon::parse($finishAt)->format('H:i:s') : null,
            'net' => $finishAt && $startAt ? (int) $startAt->diffInSeconds($finishAt) : null,
            'target' => $target,
            'target_is_own' => (bool) $reg->target_finish_minutes,
            'total_km' => $totalKm,
            'passes' => $passes->values()->all(),
            'estimate' => $estimate,
            'updated' => now()->format('H:i:s'),
        ];
    }

    /** Fungsi waktu-plan (menit) di KM tertentu, dari effort split per KM. */
    private function planElapsedFn(array $splits, ?int $target): ?\Closure
    {
        if (! $target || empty($splits)) {
            return null;
        }
        $total = array_sum(array_column($splits, 'effort')) ?: 1;
        $cum = [[0.0, 0.0]];
        $km = 0.0;
        $e = 0.0;
        foreach ($splits as $s) {
            $km += (float) $s['dist'];
            $e += (float) $s['effort'];
            $cum[] = [$km, $e];
        }

        return function (float $k) use ($cum, $total, $target): float {
            for ($i = 1; $i < count($cum); $i++) {
                if ($cum[$i][0] >= $k) {
                    [$k0, $e0] = $cum[$i - 1];
                    [$k1, $e1] = $cum[$i];
                    $e = $k1 > $k0 ? $e0 + ($e1 - $e0) * ($k - $k0) / ($k1 - $k0) : $e1;

                    return $target * $e / $total;
                }
            }

            return (float) $target;
        };
    }
}
