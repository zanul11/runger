<?php

namespace App\Filament\Resources\RosterStructures\Pages;

use App\Filament\Resources\Events\EventResource;
use App\Filament\Resources\RosterStructures\RosterStructureResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListRosterStructures extends ListRecords
{
    protected static string $resource = RosterStructureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('openRoster')->label('Buka roster (baca-saja)')->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->color('gray')->url('/crew/roster', shouldOpenInNewTab: true),
            Action::make('newEvent')->label('Event baru')->icon(Heroicon::OutlinedPlus)
                ->url(EventResource::getUrl('create')),
        ];
    }
}
