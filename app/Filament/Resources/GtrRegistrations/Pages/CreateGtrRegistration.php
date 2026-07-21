<?php

namespace App\Filament\Resources\GtrRegistrations\Pages;

use App\Filament\Resources\GtrRegistrations\GtrRegistrationResource;
use App\Models\GtrRegistration;
use App\Models\Runner;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateGtrRegistration extends CreateRecord
{
    protected static string $resource = GtrRegistrationResource::class;

    /**
     * Input manual: buat/temukan akun peserta (Runner) lalu buat pendaftaran.
     * Bila status "paid": BIB otomatis (model hook) + email konfirmasi terkirim.
     */
    protected function handleRecordCreation(array $data): Model
    {
        $email = $data['email'];
        $password = $data['password'] ?? Str::random(10);
        unset($data['password']);

        // Akun peserta: pakai yang sudah ada (berdasarkan email) atau buat baru.
        $runner = Runner::where('email', $email)->first();
        $names = preg_split('/\s+/', trim($data['full_name'] ?? 'Peserta'), 2);

        if (! $runner) {
            $runner = Runner::create([
                'first_name' => $names[0] ?: 'Peserta',
                'last_name' => $names[1] ?? null,
                'email' => $email,
                'password' => $password,
                'gender' => $this->mapGender($data['gender'] ?? null),
                'birthdate' => $data['birth_date'] ?? null,
                'phone' => $data['whatsapp'] ?? null,
            ]);
        } else {
            // Email sudah terdaftar → perbarui password sesuai input admin.
            $runner->update(['password' => $password]);
        }

        // Cegah pendaftaran ganda di kategori yang sama.
        if ($runner->registrations()->where('gtr_category_id', $data['gtr_category_id'])->exists()) {
            Notification::make()
                ->title('Peserta sudah terdaftar di kategori ini')
                ->danger()->send();

            throw ValidationException::withMessages([
                'data.email' => 'Akun ini sudah terdaftar di kategori tersebut.',
            ]);
        }

        $data['runner_id'] = $runner->id;
        $data['registered_at'] ??= now();
        $data['pay'] = $data['pay'] ?: 'Manual (Admin)';

        if (($data['payment_status'] ?? null) === 'paid') {
            $data['paid_at'] = $data['paid_at'] ?? now();
        }

        // Model hook mengisi nomor_registrasi + BIB (bila paid).
        $registration = GtrRegistration::create($data);

        // Paid → kirim email konfirmasi otomatis.
        if ($registration->payment_status === 'paid') {
            $sent = $registration->sendConfirmationEmail();
            Notification::make()
                ->title($sent ? 'Email konfirmasi terkirim' : 'Email tidak terkirim (cek log/SMTP)')
                ->{$sent ? 'success' : 'warning'}()
                ->send();
        }

        return $registration;
    }

    /** "Laki-laki"/"Perempuan" → "male"/"female" untuk akun Runner. */
    private function mapGender(?string $g): ?string
    {
        return match (true) {
            $g === null => null,
            str_starts_with(mb_strtolower($g), 'l') => 'male',
            str_starts_with(mb_strtolower($g), 'p') => 'female',
            default => null,
        };
    }
}
