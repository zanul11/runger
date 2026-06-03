<?php

namespace App\Filament\Resources\RaceCategories\Pages;

use App\Filament\Resources\RaceCategories\RaceCategoryResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewRaceCategory extends ViewRecord
{
    protected static string $resource = RaceCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
