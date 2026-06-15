<?php

namespace App\Filament\Resources\GtrRules\Pages;

use App\Filament\Resources\GtrRules\GtrRuleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditGtrRule extends EditRecord
{
    protected static string $resource = GtrRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
