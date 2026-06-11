<?php

namespace App\Filament\Resources\GtrSettings\Pages;

use App\Filament\Resources\GtrSettings\GtrSettingResource;
use App\Models\GtrSetting;
use Filament\Resources\Pages\ListRecords;

class ListGtrSettings extends ListRecords
{
    protected static string $resource = GtrSettingResource::class;

    public function mount(): void
    {
        parent::mount();

        // Singleton: jump straight to editing the one settings row.
        $this->redirect(GtrSettingResource::getUrl('edit', ['record' => GtrSetting::current()]));
    }
}
