<?php

namespace App\Filament\Resources\GtrOverviews\Pages;

use App\Filament\Resources\GtrOverviews\GtrOverviewResource;
use Filament\Resources\Pages\EditRecord;

class EditGtrOverview extends EditRecord
{
    protected static string $resource = GtrOverviewResource::class;

    protected function getRedirectUrl(): ?string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
