<?php

namespace App\Services;

use App\Models\GtrCategory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Info cuaca race day dari Open-Meteo (gratis, tanpa API key).
 *  - > 15 hari sebelum race : rata-rata iklim tanggal yang sama 5 tahun terakhir (arsip).
 *  - ≤ 15 hari sebelum race : ramalan per jam dari jam start sampai COT.
 * Hasil di-cache; bila API gagal, mengembalikan null (UI disembunyikan).
 */
class RaceWeather
{
    private const TZ = 'Asia/Makassar';

    public function forRace(): ?array
    {
        $date = Carbon::parse(config('services.gtr.race_date'), self::TZ)->startOfDay();
        [$lat, $lng] = $this->startPoint();
        if ($lat === null) {
            return null;
        }
        [$from, $to] = $this->raceWindow();

        $daysLeft = (int) now(self::TZ)->startOfDay()->diffInDays($date, false);
        if ($daysLeft < 0) {
            return null; // race sudah lewat
        }

        $data = $daysLeft <= 15
            ? $this->forecast($lat, $lng, $date, $from, $to)
            : $this->climate($lat, $lng, $date);

        if (! $data) {
            return null;
        }

        $data['tips'] = $this->tips($data);
        $data['date'] = $date->translatedFormat('l, j F Y');
        $data['days_left'] = $daysLeft;
        $data['forecast_from'] = $date->copy()->subDays(15)->translatedFormat('j M');
        $data['window'] = sprintf('%02d:%02d–%02d:%02d', intdiv($from, 60), $from % 60, intdiv($to, 60) % 24, $to % 60);

        return $data;
    }

    /** Titik start dari GPX kategori aktif pertama. */
    private function startPoint(): array
    {
        $cat = GtrCategory::where('is_active', true)->whereNotNull('route_points')->orderBy('sort_order')->first();
        $p = $cat?->route_points[0] ?? null;

        return $p ? [round((float) $p[0], 3), round((float) $p[1], 3)] : [null, null];
    }

    /** Jendela race (menit sejak 00:00): start paling awal s/d COT paling akhir semua kategori. */
    private function raceWindow(): array
    {
        $cats = GtrCategory::where('is_active', true)->get();
        $starts = $cats->map->startMinutes()->filter();
        $ends = $cats->map(fn ($c) => $c->startMinutes() !== null && $c->cutOffMinutes() ? $c->startMinutes() + $c->cutOffMinutes() : null)->filter();
        $from = $starts->min() ?? 360;

        return [$from, $ends->max() ?? $from + 240];
    }

    private function forecast(float $lat, float $lng, Carbon $date, int $from, int $to): ?array
    {
        return $this->remember("gtr_wx_fc_{$lat}_{$lng}_{$date->toDateString()}", now()->addHours(3), function () use ($lat, $lng, $date, $from, $to) {
            $res = Http::timeout(5)->get('https://api.open-meteo.com/v1/forecast', [
                'latitude' => $lat, 'longitude' => $lng, 'timezone' => self::TZ,
                'start_date' => $date->toDateString(), 'end_date' => $date->toDateString(),
                'hourly' => 'temperature_2m,apparent_temperature,precipitation_probability,precipitation,weather_code,wind_speed_10m,relative_humidity_2m',
                'daily' => 'sunrise,sunset,temperature_2m_max,temperature_2m_min,precipitation_probability_max',
            ]);
            if (! $res->ok() || ! isset($res['hourly']['time'])) {
                return null;
            }

            $h = $res['hourly'];
            $hours = [];
            foreach ($h['time'] as $i => $time) {
                $m = (int) substr($time, 11, 2) * 60;
                if ($m < intdiv($from, 60) * 60 || $m > $to) {
                    continue;
                }
                $hours[] = [
                    'time' => substr($time, 11, 5),
                    'temp' => round($h['temperature_2m'][$i]),
                    'feels' => round($h['apparent_temperature'][$i]),
                    'rain' => (int) ($h['precipitation_probability'][$i] ?? 0),
                    'mm' => (float) ($h['precipitation'][$i] ?? 0),
                    'wind' => round($h['wind_speed_10m'][$i]),
                    'humidity' => (int) ($h['relative_humidity_2m'][$i] ?? 0),
                    'code' => (int) $h['weather_code'][$i],
                ];
            }
            if (! $hours) {
                return null;
            }

            $d = $res['daily'];

            return [
                'mode' => 'forecast',
                'temp_min' => (int) round(min(array_column($hours, 'temp'))),
                'temp_max' => (int) round(max(array_column($hours, 'temp'))),
                'rain' => (int) max(array_column($hours, 'rain')),
                'wind' => (int) max(array_column($hours, 'wind')),
                'code' => $this->dominantCode(array_column($hours, 'code')),
                'sunrise' => substr($d['sunrise'][0] ?? '', 11, 5) ?: null,
                'hours' => $hours,
                'updated' => now(self::TZ)->format('H:i'),
            ];
        });
    }

    private function climate(float $lat, float $lng, Carbon $date): ?array
    {
        return $this->remember("gtr_wx_cl_{$lat}_{$lng}_{$date->format('m-d')}", now()->addDays(7), function () use ($lat, $lng, $date) {
            // 5 tahun terakhir, ±3 hari dari tanggal race — diminta paralel
            $responses = Http::pool(fn ($pool) => array_map(function ($y) use ($pool, $lat, $lng, $date) {
                $d = $date->copy()->subYears($y);

                return $pool->timeout(6)->get('https://archive-api.open-meteo.com/v1/archive', [
                    'latitude' => $lat, 'longitude' => $lng, 'timezone' => self::TZ,
                    'start_date' => $d->copy()->subDays(3)->toDateString(), 'end_date' => $d->copy()->addDays(3)->toDateString(),
                    'daily' => 'temperature_2m_max,temperature_2m_min,precipitation_sum,sunrise,wind_speed_10m_max',
                ]);
            }, range(1, 5)));

            $days = [];
            foreach ($responses as $res) {
                if (! ($res instanceof \Illuminate\Http\Client\Response) || ! $res->ok() || ! isset($res['daily']['time'])) {
                    continue;
                }
                foreach ($res['daily']['time'] as $i => $t) {
                    if ($res['daily']['temperature_2m_max'][$i] === null) {
                        continue;
                    }
                    $days[] = [
                        'max' => $res['daily']['temperature_2m_max'][$i],
                        'min' => $res['daily']['temperature_2m_min'][$i],
                        'rain' => (float) $res['daily']['precipitation_sum'][$i],
                        'wind' => (float) $res['daily']['wind_speed_10m_max'][$i],
                        'sunrise' => substr($res['daily']['sunrise'][$i] ?? '', 11, 5),
                    ];
                }
            }
            if (count($days) < 5) {
                return null;
            }

            $avg = fn ($k) => array_sum(array_column($days, $k)) / count($days);
            $sun = array_filter(array_column($days, 'sunrise'));
            $sunMin = $sun ? array_sum(array_map(fn ($s) => (int) substr($s, 0, 2) * 60 + (int) substr($s, 3, 2), $sun)) / count($sun) : null;
            $rainy = count(array_filter($days, fn ($d) => $d['rain'] >= 1)) / count($days) * 100;

            return [
                'mode' => 'climate',
                'temp_min' => (int) round($avg('min')),
                'temp_max' => (int) round($avg('max')),
                'rain' => (int) round($rainy),
                'wind' => (int) round($avg('wind')),
                'code' => $rainy >= 50 ? 61 : 2,
                'sunrise' => $sunMin !== null ? sprintf('%02d:%02d', intdiv((int) round($sunMin), 60), (int) round($sunMin) % 60) : null,
                'hours' => [],
                'years' => 5,
            ];
        });
    }

    /** Cache hasil; kegagalan API di-cache singkat agar halaman tidak menunggu berulang kali. */
    private function remember(string $key, \DateTimeInterface $ttl, \Closure $fn): ?array
    {
        $hit = Cache::get($key);
        if (is_array($hit)) {
            return ($hit['failed'] ?? false) ? null : $hit;
        }
        try {
            $val = $fn();
        } catch (\Throwable $e) {
            $val = null;
        }
        Cache::put($key, $val ?? ['failed' => true], $val ? $ttl : now()->addMinutes(20));

        return $val;
    }

        /** Kode cuaca WMO terberat yang muncul (hujan > berawan > cerah). */
    private function dominantCode(array $codes): int
    {
        return (int) (max($codes) ?: 0);
    }

    /** Label + ikon dari kode cuaca WMO. */
    public static function describe(int $code): array
    {
        return match (true) {
            $code === 0 => ['Cerah', '☀️'],
            $code <= 2 => ['Cerah berawan', '🌤️'],
            $code === 3 => ['Berawan', '☁️'],
            $code <= 48 => ['Berkabut', '🌫️'],
            $code <= 57 => ['Gerimis', '🌦️'],
            $code <= 67 => ['Hujan', '🌧️'],
            $code <= 82 => ['Hujan lokal', '🌦️'],
            default => ['Badai petir', '⛈️'],
        };
    }

    /** Pengingat gear otomatis sesuai cuaca. */
    private function tips(array $w): array
    {
        $tips = [];
        if ($w['rain'] >= 50) {
            $tips[] = 'Peluang hujan tinggi — bawa jas hujan / windbreaker & pakai sepatu dengan grip bagus, jalur tanah bisa licin.';
        } elseif ($w['rain'] >= 25) {
            $tips[] = 'Ada peluang hujan — siapkan jaket tipis tahan air.';
        }
        if ($w['temp_max'] >= 30) {
            $tips[] = 'Cuaca panas — pakai topi/visor, sunblock, dan minum elektrolit di tiap WS.';
        }
        if ($w['wind'] >= 25) {
            $tips[] = 'Angin cukup kencang di area terbuka — kencangkan perlengkapan & waspada di punggungan bukit.';
        }
        if (! $tips) {
            $tips[] = 'Cuaca relatif bersahabat — tetap bawa air & ikuti mandatory gear.';
        }

        return $tips;
    }
}
