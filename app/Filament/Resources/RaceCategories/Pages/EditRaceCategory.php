<?php

namespace App\Filament\Resources\RaceCategories\Pages;

use App\Filament\Resources\RaceCategories\RaceCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditRaceCategory extends EditRecord
{
    protected static string $resource = RaceCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
