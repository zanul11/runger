<?php

namespace App\Filament\Resources\GtrRegistrations\Pages;

use App\Filament\Resources\GtrRegistrations\GtrRegistrationResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditGtrRegistration extends EditRecord
{
    protected static string $resource = GtrRegistrationResource::class;

    protected function getHeaderActions(): array
    {
        return [ViewAction::make(), DeleteAction::make()];
    }

    /**
     * Saat status diubah menjadi "paid": BIB otomatis (model hook) + email
     * konfirmasi terkirim.
     */
    protected function afterSave(): void
    {
        // Email konfirmasi pembayaran dikirim otomatis oleh model saat status → paid.
        if ($this->record->wasChanged('payment_status') && $this->record->payment_status === 'paid') {
            Notification::make()
                ->title('Lunas — BIB dibuat & email konfirmasi pembayaran dikirim')
                ->success()
                ->send();
        }
    }
}
