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

    /** Data WS lama belum punya `type` — isi otomatis (nama diawali "CP" = Check Point). */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['water_stations'] = collect($data['water_stations'] ?? [])
            ->map(function ($p) {
                if (is_array($p) && empty($p['type'])) {
                    $p['type'] = preg_match('/^\s*cp\b|^\s*cp\d/i', (string) ($p['name'] ?? '')) ? 'cp' : 'ws';
                }

                return $p;
            })
            ->all();

        return $data;
    }
}
