<?php

namespace App\Filament\Resources\GtrTimingPoints\Pages;

use App\Filament\Resources\GtrTimingPoints\GtrTimingPointResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGtrTimingPoints extends ListRecords
{
    protected static string $resource = GtrTimingPointResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
