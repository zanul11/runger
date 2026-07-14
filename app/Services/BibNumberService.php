<?php

namespace App\Services;

use App\Models\GtrCategory;
use App\Models\GtrRegistration;
use Illuminate\Support\Facades\DB;

/**
 * Pemberian nomor BIB otomatis saat pembayaran LUNAS.
 *
 * Format: prefix kategori + urutan (auto-increment per kategori), minimal 3 digit.
 *   contoh: prefix "7" -> 7001, 7002, ... 7999, 71000, 71001, ...
 * Bila urutan > 999, otomatis jadi 4 digit (tanpa padding tambahan).
 */
class BibNumberService
{
    /**
     * Assign BIB untuk satu registrasi bila belum punya. Aman dipanggil berulang
     * (idempoten: kalau sudah ada BIB, langsung dikembalikan).
     */
    public function assignFor(GtrRegistration $registration): ?string
    {
        if (! empty($registration->bib_number)) {
            return $registration->bib_number;
        }

        return DB::transaction(function () use ($registration) {
            // Kunci baris kategori supaya penomoran serial (hindari BIB kembar
            // saat dua pembayaran lunas bersamaan).
            $category = GtrCategory::whereKey($registration->gtr_category_id)
                ->lockForUpdate()
                ->first();

            if (! $category) {
                return null;
            }

            $prefix = (string) ($category->bib_prefix ?? '');
            $nextSeq = $this->nextSequence($category, $prefix);
            $bib = $prefix . ($nextSeq >= 1000 ? (string) $nextSeq : str_pad((string) $nextSeq, 3, '0', STR_PAD_LEFT));

            $registration->forceFill(['bib_number' => $bib])->saveQuietly();

            return $bib;
        });
    }

    /**
     * Urutan berikutnya = (urutan tertinggi yang sudah dipakai di kategori) + 1.
     * Tahan terhadap lompatan/hapus karena selalu ambil MAX, bukan COUNT.
     */
    private function nextSequence(GtrCategory $category, string $prefix): int
    {
        $len = strlen($prefix);

        $maxSeq = $category->registrations()
            ->whereNotNull('bib_number')
            ->when($prefix !== '', fn ($q) => $q->where('bib_number', 'like', $prefix . '%'))
            ->pluck('bib_number')
            ->map(fn ($bib) => (int) substr((string) $bib, $len))
            ->max();

        return ((int) $maxSeq) + 1;
    }
}
