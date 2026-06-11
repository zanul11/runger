<?php

namespace App\Filament\Resources\GtrCategories\Pages;

use App\Filament\Resources\GtrCategories\GtrCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGtrCategories extends ListRecords
{
    protected static string $resource = GtrCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
