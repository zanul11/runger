<?php

namespace App\Filament\Resources\RacepackStaff\Pages;

use App\Filament\Resources\RacepackStaff\RacepackStaffResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRacepackStaff extends ListRecords
{
    protected static string $resource = RacepackStaffResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
