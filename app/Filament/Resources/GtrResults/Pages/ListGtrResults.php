<?php

namespace App\Filament\Resources\GtrResults\Pages;

use App\Filament\Resources\GtrResults\GtrResultResource;
use App\Services\ResultCalculator;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListGtrResults extends ListRecords
{
    protected static string $resource = GtrResultResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Hitung ulang semua kategori (sama dengan `php artisan timing:recompute`).
            Action::make('compute')
                ->label('Hitung Ulang Hasil')
                ->icon(Heroicon::OutlinedCalculator)
                ->color('primary')
                ->requiresConfirmation()
                ->action(function () {
                    $summary = app(ResultCalculator::class)->recomputeAll();

                    Notification::make()
                        ->title('Hasil dihitung ulang')
                        ->body("Finisher {$summary['finisher']} · DNF {$summary['dnf']} · DQ {$summary['dq']} · DNS {$summary['dns']} · Perlu review {$summary['review']}")
                        ->success()
                        ->send();
                }),
        ];
    }
}
