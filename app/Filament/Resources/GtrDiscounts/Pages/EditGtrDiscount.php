<?php

namespace App\Filament\Resources\GtrDiscounts\Pages;

use App\Filament\Resources\GtrDiscounts\GtrDiscountResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditGtrDiscount extends EditRecord
{
    protected static string $resource = GtrDiscountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
