<?php

namespace App\Filament\Resources\RacepackStaff\Pages;

use App\Filament\Resources\RacepackStaff\RacepackStaffResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRacepackStaff extends EditRecord
{
    protected static string $resource = RacepackStaffResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
