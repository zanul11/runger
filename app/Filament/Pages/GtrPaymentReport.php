<?php

namespace App\Filament\Pages;

use App\Models\GtrRegistration;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

/**
 * Laporan pembayaran GTR: pendaftar yang sudah lunas & total uang masuk,
 * dibedakan per metode pembayaran (tanpa merinci biaya layanan).
 */
class GtrPaymentReport extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|\UnitEnum|null $navigationGroup = 'GTR';

    protected static ?string $navigationLabel = 'Laporan Pembayaran';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.gtr-payment-report';

    public function getTitle(): string
    {
        return 'Laporan Pembayaran GTR';
    }

    /**
     * @return array{count:int, total:int, pending:int, by_method:array<int,array{method:string,count:int,total:int}>}
     */
    public function getReport(): array
    {
        $paid = GtrRegistration::with('category')
            ->where('payment_status', 'paid')
            ->get();

        $byMethod = $paid
            ->groupBy(fn (GtrRegistration $r) => $r->pay ?: 'Lainnya')
            ->map(fn ($group, $method) => [
                'method' => $method,
                'count' => $group->count(),
                'total' => (int) $group->sum(fn (GtrRegistration $r) => $r->totalAmount()),
            ])
            ->sortByDesc('total')
            ->values()
            ->all();

        return [
            'count' => $paid->count(),
            'total' => (int) $paid->sum(fn (GtrRegistration $r) => $r->totalAmount()),
            'pending' => GtrRegistration::where('payment_status', 'pending')->count(),
            'by_method' => $byMethod,
        ];
    }
}
