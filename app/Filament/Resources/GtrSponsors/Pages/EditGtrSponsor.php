<?php

namespace App\Filament\Resources\GtrSponsors\Pages;

use App\Filament\Resources\GtrSponsors\GtrSponsorResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditGtrSponsor extends EditRecord
{
    protected static string $resource = GtrSponsorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
