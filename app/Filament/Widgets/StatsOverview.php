<?php

namespace App\Filament\Widgets;

use App\Models\Event;
use App\Models\Volunteer;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected ?string $heading = 'Ringkasan Runger';

    protected static ?int $sort = -3;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $volTotal = Volunteer::count();
        $volPending = Volunteer::where('status', 'pending')->count();
        $volAccepted = Volunteer::where('status', 'accepted')->count();

        return [
            Stat::make('Volunteer GTR', $volTotal)
                ->description('Total pendaftar')
                ->descriptionIcon(Heroicon::OutlinedHandRaised)
                ->color('primary')
                ->chart($this->volunteerTrend()),

            Stat::make('Menunggu Review', $volPending)
                ->description('Belum diproses')
                ->descriptionIcon(Heroicon::OutlinedClock)
                ->color($volPending > 0 ? 'warning' : 'success'),

            Stat::make('Diterima', $volAccepted)
                ->description('Volunteer aktif')
                ->descriptionIcon(Heroicon::OutlinedCheckCircle)
                ->color('success'),

            Stat::make('Event', Event::count())
                ->description('Total agenda & lomba')
                ->descriptionIcon(Heroicon::OutlinedCalendarDays)
                ->color('info'),
        ];
    }

    /**
     * @return array<int, int>
     */
    protected function volunteerTrend(): array
    {
        $counts = [];

        for ($i = 6; $i >= 0; $i--) {
            $day = now('Asia/Makassar')->subDays($i)->toDateString();
            $counts[] = Volunteer::whereDate('created_at', $day)->count();
        }

        return $counts;
    }
}
