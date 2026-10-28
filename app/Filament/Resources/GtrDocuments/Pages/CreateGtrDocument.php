<?php

namespace App\Filament\Resources\GtrDocuments\Pages;

use App\Filament\Resources\GtrDocuments\GtrDocumentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateGtrDocument extends CreateRecord
{
    protected static string $resource = GtrDocumentResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
