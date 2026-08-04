<?php

namespace App\Filament\Resources\GtrDiscounts\Pages;

use App\Filament\Resources\GtrDiscounts\GtrDiscountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGtrDiscounts extends ListRecords
{
    protected static string $resource = GtrDiscountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
