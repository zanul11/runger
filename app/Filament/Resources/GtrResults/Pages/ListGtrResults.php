<?php

namespace App\Filament\Resources\GtrResults\Pages;

use App\Filament\Resources\GtrResults\GtrResultResource;
use App\Models\Event;
use App\Services\ResultEngine;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListGtrResults extends ListRecords
{
    protected static string $resource = GtrResultResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Jalankan ulang result engine (sama dengan `php artisan gtr:compute-results`).
            Action::make('compute')
                ->label('Hitung Ulang Hasil')
                ->icon(Heroicon::OutlinedCalculator)
                ->color('primary')
                ->schema([
                    Select::make('event_id')
                        ->label('Event')
                        ->options(fn () => Event::whereHas('timingPoints')->pluck('title', 'id'))
                        ->default(fn () => Event::whereHas('timingPoints')->value('id'))
                        ->required(),
                ])
                ->action(function (array $data) {
                    $event = Event::findOrFail($data['event_id']);
                    $summary = app(ResultEngine::class)->compute($event);

                    Notification::make()
                        ->title('Hasil dihitung ulang')
                        ->body("Finisher {$summary['finisher']} · DNF {$summary['dnf']} · DQ {$summary['dq']} · DNS {$summary['dns']}")
                        ->success()
                        ->send();
                }),
        ];
    }
}
