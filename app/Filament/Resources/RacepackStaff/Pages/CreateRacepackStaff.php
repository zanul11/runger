<?php

namespace App\Filament\Resources\RacepackStaff\Pages;

use App\Filament\Resources\RacepackStaff\RacepackStaffResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;

class CreateRacepackStaff extends CreateRecord
{
    protected static string $resource = RacepackStaffResource::class;

    /** Role selalu racepack (tidak bisa dipilih di form). */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $data + ['role' => User::ROLE_RACEPACK];
    }
}
