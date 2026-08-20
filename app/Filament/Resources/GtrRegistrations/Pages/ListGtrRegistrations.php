<?php

namespace App\Filament\Resources\GtrRegistrations\Pages;

use App\Filament\Resources\GtrRegistrations\GtrRegistrationResource;
use App\Models\GtrRegistration;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListGtrRegistrations extends ListRecords
{
    protected static string $resource = GtrRegistrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Input Peserta')->icon(Heroicon::OutlinedUserPlus),

            // Kirim email pengingat pembayaran ke SEMUA peserta yang masih pending.
            Action::make('reminderAll')
                ->label('Reminder Bayar (Pending)')
                ->icon(Heroicon::OutlinedBellAlert)
                ->color('warning')
                ->badge(fn () => GtrRegistration::where('payment_status', 'pending')->whereNotNull('email')->count() ?: null)
                ->requiresConfirmation()
                ->modalHeading('Kirim Pengingat Pembayaran')
                ->modalDescription(function () {
                    $n = GtrRegistration::where('payment_status', 'pending')->whereNotNull('email')->count();

                    return "Email pengingat akan dikirim ke {$n} peserta berstatus Pending (harga mengikuti yang berlaku saat ini).";
                })
                ->action(function () {
                    $ok = 0;
                    $fail = 0;
                    GtrRegistration::with('category')
                        ->where('payment_status', 'pending')
                        ->whereNotNull('email')
                        ->chunkById(100, function ($regs) use (&$ok, &$fail) {
                            foreach ($regs as $reg) {
                                $reg->sendPaymentReminder() ? $ok++ : $fail++;
                            }
                        });

                    Notification::make()
                        ->title("Pengingat terkirim ke {$ok} peserta" . ($fail ? " ({$fail} gagal)" : ''))
                        ->{$fail ? 'warning' : 'success'}()
                        ->send();
                }),

            // Unduh/cetak formulir pendaftaran kosong untuk pendaftaran offline.
            Action::make('downloadForm')
                ->label('Form Pendaftaran')
                ->icon(Heroicon::OutlinedDocumentArrowDown)
                ->color('gray')
                ->url(fn (): string => route('gtr.registration-form', ['print' => 1]))
                ->openUrlInNewTab(),
        ];
    }
}
