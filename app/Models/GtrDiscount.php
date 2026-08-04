<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GtrDiscount extends Model
{
    protected $guarded = ['id'];

    protected $attributes = [
        'type' => self::TYPE_FIXED,
        'value' => 0,
        'used_count' => 0,
        'is_active' => true,
    ];

    protected $casts = [
        'value' => 'integer',
        'quota' => 'integer',
        'used_count' => 'integer',
        'is_active' => 'boolean',
    ];

    public const TYPE_FIXED = 'fixed';
    public const TYPE_PERCENT = 'percent';

    public const TYPES = [
        self::TYPE_FIXED => 'Nominal (IDR)',
        self::TYPE_PERCENT => 'Persen (%)',
    ];

    /** Pendaftaran yang memakai voucher ini (untuk tracking pemakai). */
    public function registrations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(GtrRegistration::class, 'gtr_discount_id');
    }

    /** Sisa kuota pemakaian; null = tak terbatas. */
    public function remaining(): ?int
    {
        return $this->quota === null ? null : max(0, $this->quota - $this->used_count);
    }

    /** Masih bisa dipakai? (aktif & kuota belum habis). */
    public function isUsable(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        return $this->quota === null || $this->used_count < $this->quota;
    }

    /** Cari diskon dari kode yang masih bisa dipakai. */
    public static function findUsable(?string $code): ?self
    {
        if (! $code) {
            return null;
        }

        $discount = static::whereRaw('UPPER(code) = ?', [mb_strtoupper(trim($code))])->first();

        return $discount && $discount->isUsable() ? $discount : null;
    }

    /** Hitung potongan (Rupiah) atas nominal tertentu; tak melebihi nominal. */
    public function amountFor(int $amount): int
    {
        $cut = $this->type === self::TYPE_PERCENT
            ? (int) round($amount * min(100, $this->value) / 100)
            : $this->value;

        return max(0, min($cut, $amount));
    }

    /** Tandai satu pemakaian (aman terhadap balapan via increment atomik). */
    public function markUsed(): void
    {
        $this->increment('used_count');
    }

    /** Kembalikan satu slot kuota (tak turun di bawah 0). */
    public function markReleased(): void
    {
        if ($this->used_count > 0) {
            $this->decrement('used_count');
        }
    }

    public function getLabelAttribute(): string
    {
        return $this->type === self::TYPE_PERCENT
            ? $this->value . '%'
            : 'IDR ' . number_format($this->value, 0, ',', '.');
    }
}
