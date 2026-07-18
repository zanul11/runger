<?php

namespace App\Filament\Resources\GtrSponsors\Pages;

use App\Filament\Resources\GtrSponsors\GtrSponsorResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGtrSponsors extends ListRecords
{
    protected static string $resource = GtrSponsorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
