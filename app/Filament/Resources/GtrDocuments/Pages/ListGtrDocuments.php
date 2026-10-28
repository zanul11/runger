<?php

namespace App\Filament\Resources\GtrDocuments\Pages;

use App\Filament\Resources\GtrDocuments\GtrDocumentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGtrDocuments extends ListRecords
{
    protected static string $resource = GtrDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Dokumen Baru'),
        ];
    }
}
