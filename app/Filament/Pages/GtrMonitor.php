<?php

namespace App\Filament\Pages;

use App\Models\GtrScanLog;
use App\Models\GtrTimingPoint;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

/**
 * Monitor live per pos: marshal bertugas, jumlah scan masuk, waktu scan terakhir.
 * Auto-refresh tiap 15 detik (wire:poll di blade).
 */
class GtrMonitor extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSignal;

    protected static string|\UnitEnum|null $navigationGroup = 'Race Timing';

    protected static ?string $navigationLabel = 'Monitor Pos';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.gtr-monitor';

    public function getTitle(): string
    {
        return 'Monitor Pos Timing';
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function getRows(): array
    {
        $points = GtrTimingPoint::with(['event'])
            ->orderBy('event_id')
            ->orderBy('sort_order')
            ->get();

        // Agregasi scan per titik dalam satu query.
        $scanStats = GtrScanLog::query()
            ->selectRaw('gtr_timing_point_id, COUNT(*) as total, MAX(scanned_at) as last_scan')
            ->groupBy('gtr_timing_point_id')
            ->get()
            ->keyBy('gtr_timing_point_id');

        return $points->map(function (GtrTimingPoint $tp) use ($scanStats) {
            $marshals = $tp->assignments()
                ->where('is_active', true)
                ->with('user:id,name')
                ->get()
                ->pluck('user.name')
                ->filter()
                ->values()
                ->all();

            $stat = $scanStats->get($tp->id);

            return [
                'code' => $tp->code,
                'name' => $tp->name,
                'type' => $tp->type,
                'event' => $tp->event?->title,
                'marshals' => $marshals,
                'scan_count' => (int) ($stat->total ?? 0),
                'last_scan' => $stat?->last_scan ? \Illuminate\Support\Carbon::parse($stat->last_scan) : null,
            ];
        })->all();
    }
}
