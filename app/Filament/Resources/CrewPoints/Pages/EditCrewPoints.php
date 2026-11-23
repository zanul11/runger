<?php

namespace App\Filament\Resources\CrewPoints\Pages;

use App\Filament\Resources\CrewPoints\CrewPointResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\ValidationException;

class EditCrewPoints extends EditRecord
{
    protected static string $resource = CrewPointResource::class;

    public function getTitle(): string
    {
        return 'Sign & Marshal — ' . $this->record->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('openMap')->label('Buka peta panitia')->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->color('gray')->url('/crew/peta', shouldOpenInNewTab: true),
        ];
    }

    /**
     * Simpan langsung dari peta (geser titik / modal edit) tanpa notifikasi & redirect.
     * Mengembalikan pesan error pertama bila validasi gagal, null bila berhasil.
     */
    public function quickSave(): ?string
    {
        try {
            $this->save(shouldRedirect: false, shouldSendSavedNotification: false);
        } catch (ValidationException $e) {
            $this->setErrorBag($e->validator->errors());

            return collect($e->errors())->flatten()->first() ?: 'Data tidak valid.';
        }

        return null;
    }

    /** Simpan urut KM supaya daftar di admin & peta selalu rapi. */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        foreach (['crew_signs', 'crew_marshals'] as $key) {
            $data[$key] = collect($data[$key] ?? [])->sortBy(fn ($p) => (float) ($p['km'] ?? 0))->values()->all();
        }

        return $data;
    }

    /** Muat ulang form agar urutan baris di tabel ikut urut KM. */
    protected function afterSave(): void
    {
        $this->fillForm();
    }

    protected function getRedirectUrl(): ?string
    {
        return null; // tetap di halaman ini setelah simpan
    }
}
