<?php

namespace App\Filament\Resources\Marshals\Pages;

use App\Filament\Resources\Marshals\MarshalResource;
use Filament\Actions\DeleteAction;
use App\Models\User;
use Filament\Resources\Pages\EditRecord;

class EditMarshal extends EditRecord
{
    protected static string $resource = MarshalResource::class;

    /** Hanya peran marshal / panitia yang boleh dipilih di sini (bukan admin / racepack). */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! in_array($data['role'] ?? null, [User::ROLE_MARSHAL, User::ROLE_PANITIA], true)) {
            $data['role'] = $this->record->role;
        }

        return $data;
    }

    /** Marshal diubah jadi panitia → lepas dari pos (tidak lagi tercatat sebagai petugas pos). */
    protected function afterSave(): void
    {
        if ($this->record->isPanitia()) {
            $this->record->timingPointAssignments()->where('is_active', true)->update(['is_active' => false]);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
