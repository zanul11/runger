<?php

namespace App\Filament\Widgets;

use App\Models\GtrPayment;
use App\Models\GtrRegistration;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected ?string $heading = 'Ringkasan Pendaftaran GTR';

    protected static ?int $sort = -3;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $total = GtrRegistration::count();
        $paid = GtrRegistration::where('payment_status', 'paid')->count();
        $pending = GtrRegistration::where('payment_status', 'pending')->count();
        $revenue = (int) GtrPayment::where('status', 'paid')->sum('amount');

        return [
            Stat::make('Total Pendaftar', $total)
                ->description('Seluruh pendaftaran GTR')
                ->descriptionIcon(Heroicon::OutlinedClipboardDocumentList)
                ->color('primary')
                ->chart($this->registrationTrend()),

            Stat::make('Sudah Bayar', $paid)
                ->description('Pembayaran lunas')
                ->descriptionIcon(Heroicon::OutlinedCheckCircle)
                ->color('success'),

            Stat::make('Menunggu Bayar', $pending)
                ->description('Belum lunas')
                ->descriptionIcon(Heroicon::OutlinedClock)
                ->color($pending > 0 ? 'warning' : 'success'),

            Stat::make('Pendapatan', 'IDR ' . number_format($revenue, 0, ',', '.'))
                ->description('Total pembayaran lunas')
                ->descriptionIcon(Heroicon::OutlinedBanknotes)
                ->color('info'),
        ];
    }

    /**
     * @return array<int, int>
     */
    protected function registrationTrend(): array
    {
        $counts = [];

        for ($i = 6; $i >= 0; $i--) {
            $day = now('Asia/Makassar')->subDays($i)->toDateString();
            $counts[] = GtrRegistration::whereDate('created_at', $day)->count();
        }

        return $counts;
    }
}
