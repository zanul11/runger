<?php

namespace App\Filament\Resources\Marshals\Pages;

use App\Filament\Resources\Marshals\MarshalResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMarshal extends EditRecord
{
    protected static string $resource = MarshalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
