<?php

namespace App\Filament\Resources\GtrScenics\Pages;

use App\Filament\Resources\GtrScenics\GtrScenicResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGtrScenics extends ListRecords
{
    protected static string $resource = GtrScenicResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
