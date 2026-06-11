<?php

namespace App\Filament\Resources\GtrSettings\Pages;

use App\Filament\Resources\GtrSettings\GtrSettingResource;
use Filament\Resources\Pages\EditRecord;

class EditGtrSetting extends EditRecord
{
    protected static string $resource = GtrSettingResource::class;

    protected function getRedirectUrl(): ?string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
