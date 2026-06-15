<?php

namespace App\Filament\Resources\GtrScenics\Pages;

use App\Filament\Resources\GtrScenics\GtrScenicResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditGtrScenic extends EditRecord
{
    protected static string $resource = GtrScenicResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
