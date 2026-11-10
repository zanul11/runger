<?php

namespace App\Filament\Resources\GtrTimingPoints\Pages;

use App\Filament\Resources\GtrTimingPoints\GtrTimingPointResource;
use App\Services\GtrTimingSync;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListGtrTimingPoints extends ListRecords
{
    protected static string $resource = GtrTimingPointResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sync')
                ->label('Sinkronkan dari Titik Pos')
                ->icon(Heroicon::OutlinedArrowPath)
                ->action(function () {
                    $r = GtrTimingSync::run();
                    Notification::make()
                        ->title($r['points'] ? "{$r['points']} pos tersinkron untuk {$r['categories']} kategori" : 'Belum ada kategori dengan Titik di Rute')
                        ->{$r['points'] ? 'success' : 'warning'}()
                        ->send();
                }),
        ];
    }
}
