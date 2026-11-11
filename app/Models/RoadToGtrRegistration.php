<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/** Pendaftar "Road to GTR" — fun trail pemanasan sebelum Gerung Trail Run. */
class RoadToGtrRegistration extends Model
{
    protected $guarded = ['id'];

    /** Tanggal acara (WITA). Form ditutup setelah hari acara berakhir. */
    public const EVENT_DATE = '2026-11-15';

    /** Sesuai poster resmi (public/assets/gtr/road-to-gtr/poster.jpg). */
    public const EVENT_TIME = '06:00 WITA s.d. selesai';

    public const EVENT_PLACE = 'Bukit Keteri — Desa Banyu Urip';

    protected static function booted(): void
    {
        // Input admin juga dibakukan (08…) supaya cek dobel & link WhatsApp konsisten.
        static::saving(function (self $r) {
            $r->phone = self::normalizePhone($r->phone) ?? $r->phone;
        });
    }

    public static function eventDate(): Carbon
    {
        return Carbon::parse(self::EVENT_DATE, 'Asia/Makassar');
    }

    /** Kuota peserta (form publik). Admin tetap bisa menambah manual di Filament. */
    public const QUOTA = 100;

    /** Masih dalam periode pendaftaran (belum lewat hari acara). Kuota dicek terpisah: isFull(). */
    public static function isOpen(): bool
    {
        return now('Asia/Makassar')->lte(self::eventDate()->endOfDay());
    }

    public static function remaining(): int
    {
        return max(0, self::QUOTA - self::count());
    }

    public static function isFull(): bool
    {
        return self::remaining() === 0;
    }

    /**
     * Nomor HP → bentuk baku 08xxxxxxxxxx (dipakai untuk cek dobel). null bila bukan nomor HP Indonesia.
     * Menerima: 0812…, 62812…, +62 812-…, 812….
     */
    public static function normalizePhone(?string $raw): ?string
    {
        $d = preg_replace('/\D/', '', (string) $raw);
        if (str_starts_with($d, '62')) {
            $d = substr($d, 2);
        }
        $d = ltrim($d, '0');

        return preg_match('/^8\d{7,12}$/', $d) ? '0'.$d : null;
    }

    /** 0878-6425-4019 — untuk tampilan admin. */
    public function getPhoneFormattedAttribute(): string
    {
        return implode('-', array_filter([substr($this->phone, 0, 4), substr($this->phone, 4, 4), substr($this->phone, 8)]));
    }

    /** Link chat WhatsApp ke pendaftar. */
    public function getWhatsappUrlAttribute(): string
    {
        return 'https://wa.me/62'.substr($this->phone, 1);
    }
}
