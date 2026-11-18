<?php

namespace App\Support;

use App\Models\GtrRegistration;
use App\Models\GtrSetting;
use Illuminate\Support\Carbon;

/**
 * Kartu "Race Day" di paling atas Runger App: muncul otomatis menurut tanggal, hanya untuk peserta lunas.
 *
 *  - mode racepack: belum ambil race pack, dari H-1 hari pertama RPC s/d hari terakhir RPC
 *    (racepack_starts_on / racepack_ends_on di pengaturan GTR). Tanpa tanggal RPC → tidak tampil.
 *  - mode race: H-1 race (bila tidak sedang mode racepack) dan hari race (services.gtr.race_date).
 *
 * Setelah hari race kartu hilang (gantinya tombol Result / My Photo yang sudah ada).
 */
final class RaceDayCard
{
    public const RACEPACK = 'racepack';
    public const RACE = 'race';

    private const TZ = 'Asia/Makassar';

    /**
     * @return array{mode: string, when: string}|null when: today | tomorrow | last (hari terakhir RPC)
     */
    public static function for(GtrRegistration $reg, ?GtrSetting $setting, ?Carbon $now = null): ?array
    {
        if ($reg->payment_status !== 'paid') {
            return null;
        }

        $today = ($now ?? now())->copy()->timezone(self::TZ)->startOfDay();
        $race = Carbon::parse(config('services.gtr.race_date'), self::TZ)->startOfDay();

        if ($today->equalTo($race)) {
            return ['mode' => self::RACE, 'when' => 'today'];
        }
        if ($today->greaterThan($race)) {
            return null;
        }

        if (! $reg->racepack_collected_at && $setting?->racepack_starts_on) {
            $start = Carbon::parse($setting->racepack_starts_on->toDateString(), self::TZ);
            $end = Carbon::parse(($setting->racepack_ends_on ?? $setting->racepack_starts_on)->toDateString(), self::TZ);

            if ($today->greaterThanOrEqualTo($start->copy()->subDay()) && $today->lessThanOrEqualTo($end)) {
                return ['mode' => self::RACEPACK, 'when' => match (true) {
                    $today->lessThan($start) => 'tomorrow',
                    $today->equalTo($end) && ! $start->equalTo($end) => 'last',
                    default => 'today',
                }];
            }
        }

        if ($today->equalTo($race->copy()->subDay())) {
            return ['mode' => self::RACE, 'when' => 'tomorrow'];
        }

        return null;
    }
}
