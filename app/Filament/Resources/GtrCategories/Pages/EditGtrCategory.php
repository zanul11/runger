<?php

namespace App\Filament\Resources\GtrCategories\Pages;

use App\Filament\Resources\GtrCategories\GtrCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditGtrCategory extends EditRecord
{
    protected static string $resource = GtrCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
