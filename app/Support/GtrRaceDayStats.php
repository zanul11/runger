<?php

namespace App\Support;

use App\Models\GtrCategory;
use App\Models\GtrRegistration;
use App\Models\GtrScanLog;
use App\Models\GtrTimingPoint;
use App\Models\GtrTimingPointAssignment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Angka dashboard race day (halaman Filament GtrMonitor):
 *  - jumlah peserta tercatat per pos per kategori,
 *  - scan terakhir per device (mendeteksi HP yang offline / berhenti sinkron),
 *  - peserta di lintasan yang belum terlihat di CP terakhir (untuk sweeper).
 */
class GtrRaceDayStats
{
    /** Device dianggap "diam" bila tidak ada data masuk selama ini (menit). */
    public const QUIET_MIN = 10;

    public const STALE_MIN = 20;

    /**
     * @return array{categories: Collection, rows: list<array>}
     */
    public static function postMatrix(): array
    {
        $categories = GtrCategory::orderBy('sort_order')->orderBy('id')->get(['id', 'name']);
        $eligible = GtrRegistration::query()->raceEligible()->selectRaw('gtr_category_id, COUNT(*) as n')
            ->groupBy('gtr_category_id')->pluck('n', 'gtr_category_id');

        // Peserta unik dengan scan sah per (pos, kategori).
        $seen = GtrScanLog::query()->valid()
            ->join('gtr_registrations as r', 'r.id', '=', 'gtr_scan_logs.gtr_registration_id')
            ->selectRaw('gtr_scan_logs.gtr_timing_point_id as tp, r.gtr_category_id as cat, COUNT(DISTINCT gtr_scan_logs.gtr_registration_id) as n, MAX(gtr_scan_logs.scanned_at) as last_at')
            ->groupBy('gtr_scan_logs.gtr_timing_point_id', 'r.gtr_category_id')
            ->get()
            ->keyBy(fn ($r) => $r->tp.':'.$r->cat);

        $rows = GtrTimingPoint::with('categories:id')->orderBy('sort_order')->get()->map(function (GtrTimingPoint $tp) use ($categories, $seen, $eligible) {
            $cells = [];
            $last = null;
            foreach ($categories as $c) {
                if (! $tp->categories->contains('id', $c->id)) {
                    $cells[$c->id] = null; // kategori tidak lewat pos ini

                    continue;
                }
                $s = $seen->get($tp->id.':'.$c->id);
                $cells[$c->id] = ['seen' => (int) ($s->n ?? 0), 'total' => (int) ($eligible[$c->id] ?? 0)];
                if ($s?->last_at && (! $last || $s->last_at > $last)) {
                    $last = $s->last_at;
                }
            }

            return [
                'code' => $tp->code,
                'name' => $tp->name,
                'type' => $tp->type,
                'cells' => $cells,
                'last_at' => $last ? Carbon::parse($last) : null,
                'marshals' => $tp->assignments()->where('is_active', true)->with('user:id,name')->get()->pluck('user.name')->filter()->values()->all(),
            ];
        })->all();

        return ['categories' => $categories, 'rows' => $rows];
    }

    /** @return list<array> device terakhir aktif lebih dulu */
    public static function devices(): array
    {
        $now = now();
        $stats = GtrScanLog::query()->whereNotNull('device_id')
            ->selectRaw('device_id, COUNT(*) as total, MAX(server_received_at) as last_received, MAX(scanned_at) as last_scanned, MAX(id) as last_id')
            ->groupBy('device_id')
            ->get();
        $lastRows = GtrScanLog::with(['scanner:id,name', 'timingPoint:id,code,name'])->whereIn('id', $stats->pluck('last_id'))->get()->keyBy('id');

        $devices = $stats->map(function ($s) use ($lastRows, $now) {
            $row = $lastRows->get($s->last_id);
            $received = $s->last_received ? Carbon::parse($s->last_received) : null;

            return [
                'device_id' => $s->device_id,
                'user' => $row?->scanner?->name,
                'point' => $row?->timingPoint ? $row->timingPoint->code.' · '.$row->timingPoint->name : '—',
                'total' => (int) $s->total,
                'last_received' => $received,
                'last_scanned' => $s->last_scanned ? Carbon::parse($s->last_scanned) : null,
                'state' => self::state($received, $now),
            ];
        });

        // Device yang sudah login (assignment aktif) tapi belum mengirim scan sama sekali.
        $silent = GtrTimingPointAssignment::with(['user:id,name', 'timingPoint:id,code,name'])
            ->where('is_active', true)->whereNotNull('device_id')
            ->whereNotIn('device_id', $stats->pluck('device_id'))
            ->get()
            ->map(fn (GtrTimingPointAssignment $a) => [
                'device_id' => $a->device_id,
                'user' => $a->user?->name,
                'point' => $a->timingPoint ? $a->timingPoint->code.' · '.$a->timingPoint->name : '—',
                'total' => 0,
                'last_received' => $a->device_seen_at,
                'last_scanned' => null,
                'state' => 'idle',
            ]);

        return $devices->concat($silent)
            ->sortByDesc(fn ($d) => $d['last_received']?->getTimestamp() ?? 0)
            ->values()->all();
    }

    private static function state(?Carbon $received, Carbon $now): string
    {
        if (! $received) {
            return 'idle';
        }
        $min = $received->diffInMinutes($now);

        return $min >= self::STALE_MIN ? 'stale' : ($min >= self::QUIET_MIN ? 'quiet' : 'ok');
    }

    /**
     * Per kategori: peserta yang sudah tercatat di lintasan tetapi belum terlihat di CP terakhir
     * (pos sebelum finish) dan belum finish — posisi terakhir & jamnya untuk sweeper.
     *
     * @return list<array{category: string, last_cp: ?string, on_course: int, not_started: int, finished: int, rows: list<array>}>
     */
    public static function notSeenAtLastCp(): array
    {
        $out = [];
        foreach (GtrCategory::orderBy('sort_order')->orderBy('id')->get() as $cat) {
            $route = $cat->timingPoints()->get();
            $finish = $route->firstWhere('type', GtrTimingPoint::TYPE_FINISH);
            $lastCp = $route->filter(fn ($p) => ! in_array($p->type, [GtrTimingPoint::TYPE_START, GtrTimingPoint::TYPE_FINISH], true))->last();

            $regs = GtrRegistration::query()->raceEligible()->where('gtr_category_id', $cat->id)->get(['id', 'bib_number', 'full_name', 'gender', 'whatsapp', 'emergency_contact']);
            $scans = GtrScanLog::query()->valid()
                ->whereIn('gtr_registration_id', $regs->pluck('id'))
                ->whereIn('gtr_timing_point_id', $route->pluck('id'))
                ->get(['gtr_registration_id', 'gtr_timing_point_id', 'scanned_at'])
                ->groupBy('gtr_registration_id');

            $rows = [];
            $finished = 0;
            $notStarted = 0;
            foreach ($regs as $reg) {
                $mine = $scans->get($reg->id);
                if (! $mine) {
                    $notStarted++;

                    continue;
                }
                $points = $mine->pluck('gtr_timing_point_id');
                if ($finish && $points->contains($finish->id)) {
                    $finished++;

                    continue;
                }
                if ($lastCp && $points->contains($lastCp->id)) {
                    continue; // sudah lewat CP terakhir, menuju finish
                }
                $latest = $mine->sortByDesc(fn ($s) => $s->scanned_at->getTimestampMs())->first();
                $rows[] = [
                    'bib' => $reg->bib_number,
                    'name' => $reg->full_name,
                    'gender' => $reg->genderCode(),
                    'last_point' => optional($route->firstWhere('id', $latest->gtr_timing_point_id))->code,
                    'last_at' => $latest->scanned_at,
                    'phone' => $reg->whatsapp,
                    'emergency' => $reg->emergency_contact,
                ];
            }
            usort($rows, fn ($a, $b) => $a['last_at'] <=> $b['last_at']); // paling lama tak terlihat di atas

            $out[] = [
                'category' => $cat->name,
                'last_cp' => $lastCp ? $lastCp->code.' · '.$lastCp->name : null,
                'on_course' => count($rows),
                'not_started' => $notStarted,
                'finished' => $finished,
                'rows' => $rows,
            ];
        }

        return $out;
    }
}
