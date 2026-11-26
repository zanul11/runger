<?php

namespace App\Services;

use App\Models\GtrRacepackLog;
use App\Models\GtrRegistration;
use App\Models\User;
use App\Support\Bib;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Check-in pengambilan racepack (H-2) dari scanner. Kode = nomor BIB (QR BIB / keypad)
 * atau nomor registrasi (QR e-ticket, "GTR2026…"). Idempoten: check-in ulang menjawab
 * "already" tanpa mengubah data, jadi aman dikirim ulang dari antrean offline.
 */
class GtrRacepackCheckin
{
    public const OK = 'ok';

    public const ALREADY = 'already';

    public const NOT_FOUND = 'not_found';

    public const NOT_PAID = 'not_paid';

    public function resolve(mixed $code): ?GtrRegistration
    {
        if ($regNo = Bib::normalizeRegistrationNumber($code)) {
            return GtrRegistration::where('nomor_registrasi', $regNo)->first();
        }

        $bib = Bib::normalize($code);
        if ($bib === null) {
            return null;
        }

        // Semua status (bukan hanya lunas) supaya bisa menjawab "belum lunas" dengan jelas.
        return GtrRegistration::where('bib_number', $bib)->first() ?? GtrRegistration::findByBib($bib);
    }

    public const AVAILABLE = 'available';

    /**
     * Cek peserta SEBELUM racepack diserahkan (tidak mengubah data).
     *
     * @return array{status: string, registration: ?GtrRegistration} status: available|already|not_paid|not_found
     */
    public function lookup(mixed $code): array
    {
        $reg = $this->resolve($code);

        return match (true) {
            ! $reg => ['status' => self::NOT_FOUND, 'registration' => null],
            $reg->payment_status !== 'paid' => ['status' => self::NOT_PAID, 'registration' => $reg],
            $reg->racepack_collected_at !== null => ['status' => self::ALREADY, 'registration' => $reg->load(['category', 'racepackCollector'])],
            default => ['status' => self::AVAILABLE, 'registration' => $reg->load('category')],
        };
    }

    /**
     * @return array{status: string, registration: ?GtrRegistration}
     */
    public function checkin(mixed $code, User $by, ?string $pickedBy = null, ?Carbon $at = null): array
    {
        $reg = $this->resolve($code);

        if (! $reg) {
            return ['status' => self::NOT_FOUND, 'registration' => null];
        }
        if ($reg->payment_status !== 'paid') {
            return ['status' => self::NOT_PAID, 'registration' => $reg];
        }

        $updated = $this->collect($reg, $by, $pickedBy, $at);

        return ['status' => $updated ? self::OK : self::ALREADY, 'registration' => $reg->fresh(['category', 'racepackCollector'])];
    }

    /** Tandai diambil + catat log. False bila sudah diambil sebelumnya (keduluan petugas lain). */
    public function collect(GtrRegistration $reg, User $by, ?string $pickedBy = null, ?Carbon $at = null): bool
    {
        $at ??= now();
        $pickedBy = trim((string) $pickedBy) ?: null;

        return DB::transaction(function () use ($reg, $by, $pickedBy, $at) {
            $updated = GtrRegistration::whereKey($reg->id)->whereNull('racepack_collected_at')->update([
                'racepack_collected_at' => $at,
                'racepack_collected_by' => $by->id,
                'racepack_picked_by' => $pickedBy,
                'updated_at' => now(),
            ]);
            if ($updated) {
                GtrRacepackLog::create(['gtr_registration_id' => $reg->id, 'collected_at' => $at, 'collected_by' => $by->id, 'picked_by' => $pickedBy]);
            }

            return (bool) $updated;
        });
    }

    /** Batalkan pengambilan: status registrasi dikosongkan, log ditandai dibatalkan (tetap di riwayat). */
    public function cancel(GtrRegistration $reg, User $by): bool
    {
        return DB::transaction(function () use ($reg, $by) {
            $updated = GtrRegistration::whereKey($reg->id)->whereNotNull('racepack_collected_at')->update([
                'racepack_collected_at' => null,
                'racepack_collected_by' => null,
                'racepack_picked_by' => null,
                'updated_at' => now(),
            ]);
            if ($updated) {
                GtrRacepackLog::where('gtr_registration_id', $reg->id)->whereNull('cancelled_at')
                    ->update(['cancelled_at' => now(), 'cancelled_by' => $by->id, 'updated_at' => now()]);
            }

            return (bool) $updated;
        });
    }
}
