<?php

namespace App\Filament\Widgets;

use App\Models\GtrRegistration;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class GtrPaymentStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $paid = GtrRegistration::with('category')
            ->where('payment_status', 'paid')
            ->get();

        $pending = GtrRegistration::where('payment_status', 'pending')->count();

        // Total uang masuk = biaya pendaftaran + biaya layanan (0 bila non-QRIS).
        $totalMoney = $paid->sum(fn (GtrRegistration $r) => $r->totalAmount());
        $totalBase = $paid->sum(fn (GtrRegistration $r) => $r->baseAmount());
        $totalFee = $paid->sum(fn (GtrRegistration $r) => $r->serviceFee());

        $idr = fn ($n) => 'IDR ' . number_format((int) $n, 0, ',', '.');

        return [
            Stat::make('Pendaftar Lunas', number_format($paid->count(), 0, ',', '.'))
                ->description($pending . ' menunggu pembayaran')
                ->descriptionIcon('heroicon-o-clock')
                ->color('success'),

            Stat::make('Total Uang Masuk', $idr($totalMoney))
                ->description('Pendaftaran ' . $idr($totalBase) . ' + layanan ' . $idr($totalFee))
                ->descriptionIcon('heroicon-o-banknotes')
                ->color('primary'),

            Stat::make('Biaya Layanan (QRIS)', $idr($totalFee))
                ->description('Dari ' . $paid->filter(fn ($r) => $r->isQris())->count() . ' pembayaran QRIS')
                ->descriptionIcon('heroicon-o-qr-code')
                ->color('warning'),
        ];
    }
}
