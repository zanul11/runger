<?php

namespace App\Filament\Resources\GtrRules\Pages;

use App\Filament\Resources\GtrRules\GtrRuleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGtrRules extends ListRecords
{
    protected static string $resource = GtrRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
