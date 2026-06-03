<?php

namespace App\Filament\Resources\ContactChannels\Pages;

use App\Filament\Resources\ContactChannels\ContactChannelResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewContactChannel extends ViewRecord
{
    protected static string $resource = ContactChannelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
