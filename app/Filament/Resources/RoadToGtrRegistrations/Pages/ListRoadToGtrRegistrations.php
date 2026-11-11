<?php

namespace App\Filament\Resources\RoadToGtrRegistrations\Pages;

use App\Filament\Resources\RoadToGtrRegistrations\RoadToGtrRegistrationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRoadToGtrRegistrations extends ListRecords
{
    protected static string $resource = RoadToGtrRegistrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
