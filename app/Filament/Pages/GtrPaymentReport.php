<?php

namespace App\Filament\Pages;

use App\Models\GtrRegistration;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

/**
 * Laporan pembayaran GTR: pendaftar lunas & total uang masuk, dibedakan per
 * metode & kategori (tanpa merinci biaya layanan). Bisa dicetak.
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

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print')
                ->label('Cetak Laporan')
                ->icon(Heroicon::OutlinedPrinter)
                ->color('gray')
                ->url(fn (): string => route('gtr.payment-report', ['print' => 1]))
                ->openUrlInNewTab(),
        ];
    }

    public function getReport(): array
    {
        return GtrRegistration::paymentReport();
    }
}
