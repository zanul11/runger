<?php

namespace App\Filament\Resources\CrewPoints\Pages;

use App\Filament\Resources\CrewPoints\CrewPointResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListCrewPoints extends ListRecords
{
    protected static string $resource = CrewPointResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('openMap')->label('Buka peta panitia')->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->color('gray')->url('/crew/peta', shouldOpenInNewTab: true),
        ];
    }
}
