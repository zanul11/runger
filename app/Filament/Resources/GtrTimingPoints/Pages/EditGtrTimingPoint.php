<?php

namespace App\Filament\Resources\GtrTimingPoints\Pages;

use App\Filament\Resources\GtrTimingPoints\GtrTimingPointResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditGtrTimingPoint extends EditRecord
{
    protected static string $resource = GtrTimingPointResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
