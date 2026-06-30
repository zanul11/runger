<?php

namespace App\Filament\Resources\Marshals\Pages;

use App\Filament\Resources\Marshals\MarshalResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMarshals extends ListRecords
{
    protected static string $resource = MarshalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Tambah Marshal'),
        ];
    }
}
