<?php

namespace App\Casts;

use Carbon\CarbonInterface;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Kolom DATETIME(3): simpan & baca waktu dengan presisi milidetik (zona app, Asia/Makassar).
 *
 * Cast `datetime` bawaan memakai format 'Y-m-d H:i:s' sehingga milidetik hilang saat disimpan.
 * Cast ini dipakai khusus kolom timing, tanpa mengubah $dateFormat model (kolom lain aman).
 * Nilai integer dianggap epoch milidetik.
 */
class DateTimeMs implements CastsAttributes
{
    public const FORMAT = 'Y-m-d H:i:s.v';

    public function get(Model $model, string $key, mixed $value, array $attributes): ?Carbon
    {
        return $value === null ? null : Carbon::parse($value, config('app.timezone'));
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return self::toDb($value);
    }

    public static function toDb(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return self::toCarbon($value)->format(self::FORMAT);
    }

    public static function toCarbon(mixed $value): Carbon
    {
        $tz = config('app.timezone');

        return match (true) {
            $value instanceof CarbonInterface => Carbon::instance($value)->setTimezone($tz),
            $value instanceof \DateTimeInterface => Carbon::instance($value)->setTimezone($tz),
            is_int($value) => Carbon::createFromTimestampMs($value, $tz),
            default => Carbon::parse($value, $tz),
        };
    }

    /** Carbon → epoch milidetik (null aman). */
    public static function toMs(?CarbonInterface $value): ?int
    {
        return $value === null ? null : (int) $value->getTimestampMs();
    }
}
