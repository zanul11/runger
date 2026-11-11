<?php

namespace App\Filament\Resources\RoadToGtrRegistrations\Pages;

use App\Filament\Resources\RoadToGtrRegistrations\RoadToGtrRegistrationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRoadToGtrRegistration extends EditRecord
{
    protected static string $resource = RoadToGtrRegistrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
