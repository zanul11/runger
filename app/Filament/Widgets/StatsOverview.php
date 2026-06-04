<?php

namespace App\Filament\Widgets;

use App\Models\Event;
use App\Models\GalleryItem;
use App\Models\Member;
use App\Models\Participant;
use App\Models\Volunteer;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected ?string $heading = 'Ringkasan Runger';

    protected static ?int $sort = -2;

    protected function getStats(): array
    {
        $volTotal = Volunteer::count();
        $volPending = Volunteer::where('status', 'pending')->count();

        return [
            Stat::make('Volunteer GTR', $volTotal)
                ->description($volPending . ' menunggu review')
                ->descriptionIcon(Heroicon::OutlinedClock)
                ->color($volPending > 0 ? 'warning' : 'success')
                ->chart($this->volunteerTrend()),

            Stat::make('Event', Event::count())
                ->description('Total agenda & lomba')
                ->descriptionIcon(Heroicon::OutlinedCalendarDays)
                ->color('info'),

            Stat::make('Pengurus', Member::count())
                ->description('Anggota tim komunitas')
                ->descriptionIcon(Heroicon::OutlinedUserGroup)
                ->color('primary'),

            Stat::make('Peserta', Participant::count())
                ->description('Terdaftar di event')
                ->descriptionIcon(Heroicon::OutlinedUsers)
                ->color('success'),

            Stat::make('Galeri', GalleryItem::count())
                ->description('Foto dokumentasi')
                ->descriptionIcon(Heroicon::OutlinedPhoto)
                ->color('gray'),
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
