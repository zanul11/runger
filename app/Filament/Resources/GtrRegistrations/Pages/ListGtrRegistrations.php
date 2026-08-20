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

    /** Peserta belum lunas yang punya email (pending + cancelled). */
    protected static function unpaidQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return GtrRegistration::whereIn('payment_status', ['pending', 'cancelled'])
            ->whereNotNull('email')
            ->where('email', '!=', '');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Input Peserta')->icon(Heroicon::OutlinedUserPlus),

            // Kirim email pengingat pembayaran ke SEMUA yang BELUM lunas (pending + cancelled).
            Action::make('reminderAll')
                ->label('Reminder Bayar')
                ->icon(Heroicon::OutlinedBellAlert)
                ->color('warning')
                ->badge(fn () => self::unpaidQuery()->count() ?: null)
                ->requiresConfirmation()
                ->modalHeading('Kirim Pengingat Pembayaran')
                ->modalDescription(fn () => 'Email pengingat akan dikirim ke ' . self::unpaidQuery()->count()
                    . ' peserta yang belum lunas (status Pending & Cancelled). Harga mengikuti yang berlaku saat ini.')
                ->action(function () {
                    // Batch besar: cegah request timeout & jangan hentikan batch bila 1 gagal.
                    @set_time_limit(0);
                    ignore_user_abort(true);

                    $ok = 0;
                    $fail = 0;
                    self::unpaidQuery()->with('category')
                        ->chunkById(100, function ($regs) use (&$ok, &$fail) {
                            foreach ($regs as $reg) {
                                $reg->sendPaymentReminder() ? $ok++ : $fail++;
                            }
                        });

                    Notification::make()
                        ->title("Pengingat terkirim ke {$ok} peserta" . ($fail ? " · {$fail} gagal (cek log)" : ''))
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
