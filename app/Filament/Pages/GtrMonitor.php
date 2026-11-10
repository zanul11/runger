<?php

namespace App\Filament\Pages;

use App\Support\GtrRaceDayStats;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

/**
 * Dashboard race day (auto-refresh 15 detik):
 *  - peserta tercatat per pos per kategori + marshal aktif,
 *  - scan terakhir per device (deteksi HP offline / berhenti sinkron),
 *  - peserta di lintasan yang belum terlihat di CP terakhir (untuk sweeper).
 */
class GtrMonitor extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSignal;

    protected static string|\UnitEnum|null $navigationGroup = 'Race Timing';

    protected static ?string $navigationLabel = 'Race Day';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.gtr-monitor';

    public function getTitle(): string
    {
        return 'Dashboard Race Day';
    }

    public function getMatrix(): array
    {
        return GtrRaceDayStats::postMatrix();
    }

    public function getDevices(): array
    {
        return GtrRaceDayStats::devices();
    }

    public function getSweep(): array
    {
        return GtrRaceDayStats::notSeenAtLastCp();
    }
}
