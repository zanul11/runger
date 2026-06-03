<?php

namespace App\Filament\Resources\RaceCategories\Pages;

use App\Filament\Resources\RaceCategories\RaceCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRaceCategories extends ListRecords
{
    protected static string $resource = RaceCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
