<?php

namespace App\Models;

use App\Services\GpxParser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class GtrCategory extends Model
{
    protected $guarded = ['id'];

    public function registrations(): HasMany
    {
        return $this->hasMany(GtrRegistration::class, 'gtr_category_id');
    }

    /** Titik timing yang harus dilewati kategori ini, urut sesuai sequence. */
    public function timingPoints(): BelongsToMany
    {
        return $this->belongsToMany(GtrTimingPoint::class, 'gtr_category_timing_point')
            ->withPivot(['sequence', 'km', 'is_mandatory', 'cutoff_at'])
            ->withTimestamps()
            ->orderBy('gtr_category_timing_point.sequence');
    }

    protected $casts = [
        'route_points' => 'array',
        'elevation_profile' => 'array',
        'water_stations' => 'array',
        'course_pois' => 'array',
        'mandatory_gear' => 'array',
        'rundown' => 'array',
        'early_bird_until' => 'date',
        'gun_start' => 'datetime',
        'cut_off_at' => 'datetime',
        'total_km' => 'decimal:2',
        'price_early_bird' => 'integer',
        'price_normal' => 'integer',
        'is_active' => 'boolean',
        'gpx_downloadable' => 'boolean',
    ];

    /**
     * Parse the uploaded GPX into route points (for the course map) whenever it changes.
     */
    protected static function booted(): void
    {
        static::saving(function (GtrCategory $cat) {
            if (! $cat->isDirty('gpx_file')) {
                return;
            }

            if (! $cat->gpx_file) {
                $cat->route_points = null;
                $cat->elevation_profile = null;
                $cat->total_km = null;

                return;
            }

            $disk = Storage::disk('public');
            if (! $disk->exists($cat->gpx_file)) {
                return;
            }

            $parsed = GpxParser::parse($disk->path($cat->gpx_file));
            $cat->route_points = $parsed['route_points'] ?? null;
            $cat->elevation_profile = $parsed['elevation_profile'] ?? null;
            $cat->total_km = $parsed['total_km'] ?? null;
        });
    }

    public function getHeaderUrlAttribute(): string
    {
        return $this->header_image
            ? Storage::disk('public')->url($this->header_image)
            : asset('assets/gtr/gallery/rudi.webp');
    }

    public function getGpxUrlAttribute(): ?string
    {
        return $this->gpx_file ? Storage::disk('public')->url($this->gpx_file) : null;
    }

    /** GPX boleh diunduh peserta: diizinkan admin & filenya ada. */
    public function canDownloadGpx(): bool
    {
        return $this->gpx_downloadable
            && $this->gpx_file
            && Storage::disk('public')->exists($this->gpx_file);
    }

    /**
     * Elevation gain read live from the GPX file (cached by file mtime), never from a DB column.
     * Returns a formatted string like "+344 M", or null when there is no GPX / no elevation data.
     */
    public function gpxElevationGain(): ?string
    {
        if (! $this->gpx_file) {
            return null;
        }

        $disk = Storage::disk('public');
        if (! $disk->exists($this->gpx_file)) {
            return null;
        }

        $abs = $disk->path($this->gpx_file);
        $gain = Cache::remember(
            'gtr_eg_' . md5($this->gpx_file . '|' . @filemtime($abs)),
            3600,
            fn () => GpxParser::parse($abs)['elevation_gain_m'] ?? null,
        );

        return $gain !== null ? '+' . round($gain) . ' M' : null;
    }

    public function priceFormatted(?int $value): string
    {
        return $value ? 'IDR ' . number_format($value, 0, ',', '.') : '-';
    }

    public function getEarlyBirdFormattedAttribute(): string
    {
        return $this->priceFormatted($this->price_early_bird);
    }

    public function getWaterStationCountAttribute(): int
    {
        return $this->coursePoints()->where('type', 'ws')->count();
    }

    // ===== Titik di rute (WS & Check Point) — disimpan di kolom water_stations =====

    public const POINT_TYPES = [
        'ws' => 'Water Station',
        'cp' => 'Check Point',
    ];

    /**
     * WS & CP dinormalisasi, urut KM, dengan kode otomatis WS1.. / CP1...
     * Data lama tanpa `type` dianggap WS, kecuali namanya diawali "CP".
     */
    public function coursePoints(): Collection
    {
        $counter = ['ws' => 0, 'cp' => 0];

        return collect($this->water_stations ?? [])
            ->filter(fn ($p) => is_array($p) && isset($p['km']) && is_numeric($p['km']))
            ->map(function (array $p) {
                $name = trim((string) ($p['name'] ?? ''));
                $type = $p['type'] ?? (preg_match('/^\s*cp\b|^\s*cp\d/i', $name) ? 'cp' : 'ws');
                $type = $type === 'cp' ? 'cp' : 'ws';

                return [
                    'type' => $type,
                    'km' => round((float) $p['km'], 2),
                    // buang prefix "WS 1 -" / "CP1:" dari nama, kodenya sudah ditampilkan terpisah
                    'name' => trim(preg_replace('/^\s*(ws|cp)\s*\d*\s*[-–:·]?\s*/i', '', $name)) ?: null,
                    'location' => trim((string) ($p['location'] ?? '')) ?: null,
                    'facilities' => array_values(array_filter(array_map('trim', (array) ($p['facilities'] ?? [])))),
                    'cutoff' => preg_match('/^\s*(\d{1,2})[:.](\d{2})\s*$/', (string) ($p['cutoff'] ?? ''), $m)
                        ? sprintf('%02d:%02d', $m[1], $m[2]) : null,
                    'notes' => trim((string) ($p['notes'] ?? '')) ?: null,
                ];
            })
            ->sortBy('km')
            ->values()
            ->map(function (array $p) use (&$counter) {
                $counter[$p['type']]++;
                $p['code'] = strtoupper($p['type']) . $counter[$p['type']];
                $p['type_label'] = self::POINT_TYPES[$p['type']];
                $p['title'] = $p['name'] ?: $p['type_label'];

                return $p;
            });
    }

    /** Tipe info/bahaya jalur: label, ikon, dan apakah termasuk peringatan (hazard). */
    public const POI_TYPES = [
        'technical' => ['label' => 'Jalur Teknikal / Licin', 'icon' => '⚠️', 'hazard' => true],
        'descent' => ['label' => 'Turunan Curam', 'icon' => '⬇️', 'hazard' => true],
        'climb' => ['label' => 'Tanjakan Curam', 'icon' => '⛰️', 'hazard' => true],
        'river' => ['label' => 'Penyeberangan Sungai', 'icon' => '🌊', 'hazard' => true],
        'road' => ['label' => 'Menyeberang Jalan Raya', 'icon' => '🚗', 'hazard' => true],
        'marshal' => ['label' => 'Marshal', 'icon' => '🦺', 'hazard' => false],
        'medic' => ['label' => 'Pos Medis / Ambulans', 'icon' => '🚑', 'hazard' => false],
        'evac' => ['label' => 'Titik Evakuasi', 'icon' => '🆘', 'hazard' => false],
        'photo' => ['label' => 'Spot Foto', 'icon' => '📸', 'hazard' => false],
        'toilet' => ['label' => 'Toilet', 'icon' => '🚻', 'hazard' => false],
        'parking' => ['label' => 'Parkir', 'icon' => '🅿️', 'hazard' => false],
        'info' => ['label' => 'Info Lain', 'icon' => 'ℹ️', 'hazard' => false],
    ];

    /** Pilihan tipe POI untuk form admin. */
    public static function poiTypeOptions(): array
    {
        return collect(self::POI_TYPES)->map(fn ($t) => $t['icon'] . '  ' . $t['label'])->all();
    }

    /** Info & bahaya jalur dinormalisasi, urut KM. `km_end` opsional untuk satu ruas. */
    public function coursePois(): Collection
    {
        return collect($this->course_pois ?? [])
            ->filter(fn ($p) => is_array($p) && isset($p['km']) && is_numeric($p['km']) && isset(self::POI_TYPES[$p['type'] ?? '']))
            ->map(function (array $p) {
                $def = self::POI_TYPES[$p['type']];
                $km = round((float) $p['km'], 2);
                $end = isset($p['km_end']) && is_numeric($p['km_end']) && (float) $p['km_end'] > $km ? round((float) $p['km_end'], 2) : null;

                return [
                    'type' => $p['type'],
                    'km' => $km,
                    'km_end' => $end,
                    'title' => trim((string) ($p['title'] ?? '')) ?: $def['label'],
                    'note' => trim((string) ($p['note'] ?? '')) ?: null,
                    'label' => $def['label'],
                    'icon' => $def['icon'],
                    'hazard' => $def['hazard'],
                ];
            })
            ->sortBy('km')
            ->values();
    }

    /**
     * Race plan untuk target waktu tertentu: jam tiba (menit sejak 00:00) & waktu tempuh di tiap pos.
     * Algoritma sama dengan estimator di halaman (waktu dibagi sesuai effort segmen).
     */
    public function racePlan(int $targetMinutes): array
    {
        $course = $this->courseSegments();
        if (empty($course['segments'])) {
            return [];
        }

        $effort = array_sum(array_column($course['segments'], 'effort')) ?: 1;
        $start = $this->startMinutes();
        $cot = $this->cutOffMinutes();
        $elapsed = 0.0;
        $rows = [];

        foreach ($course['stops'] as $i => $s) {
            if ($i > 0) {
                $elapsed += $targetMinutes * $course['segments'][$i - 1]['effort'] / $effort;
            }
            $cut = $s['type'] === 'finish'
                ? ($start !== null && $cot ? $start + $cot : null)
                : (! empty($s['cutoff']) ? ((int) substr($s['cutoff'], 0, 2)) * 60 + (int) substr($s['cutoff'], 3, 2) : null);

            $rows[] = [
                'code' => $s['code'],
                'type' => $s['type'],
                'title' => $s['title'] ?? $s['code'],
                'km' => (float) $s['km'],
                'elapsed' => (int) round($elapsed),
                'eta' => $start !== null ? (int) round($start + $elapsed) : null,
                'cutoff' => $cut,
                'segment' => $i > 0 ? $course['segments'][$i - 1] : null,
            ];
        }

        return $rows;
    }

    /** Race plan per KM: pace & jam tiba di akhir tiap KM untuk target tertentu. */
    public function racePlanSplits(int $targetMinutes): array
    {
        $splits = $this->courseSegments()['splits'] ?? [];
        if (empty($splits)) {
            return [];
        }
        $effort = array_sum(array_column($splits, 'effort')) ?: 1;
        $start = $this->startMinutes();
        $elapsed = 0.0;

        return array_map(function (array $s) use (&$elapsed, $targetMinutes, $effort, $start) {
            $dur = $targetMinutes * $s['effort'] / $effort;
            $elapsed += $dur;

            return $s + [
                'pace' => $s['dist'] > 0 ? $dur / $s['dist'] : 0, // menit per km
                'elapsed' => (int) round($elapsed),
                'eta' => $start !== null ? (int) round($start + $elapsed) : null,
            ];
        }, $splits);
    }

        /**
     * Segmen antar pos (Start → WS/CP → Finish) dihitung dari profil elevasi:
     * jarak, naik/turun, gradien maksimum, karakter, dan "effort" untuk estimasi waktu.
     */
    public function courseSegments(): array
    {
        $ep = array_values(array_filter($this->elevation_profile ?? [], fn ($p) => is_array($p) && count($p) >= 2));
        if (count($ep) < 2) {
            return [];
        }

        $total = (float) $ep[count($ep) - 1][0];
        $eleAt = function (float $km) use ($ep): float {
            for ($i = 1; $i < count($ep); $i++) {
                if ((float) $ep[$i][0] >= $km) {
                    [$k0, $e0] = [(float) $ep[$i - 1][0], (float) $ep[$i - 1][1]];
                    [$k1, $e1] = [(float) $ep[$i][0], (float) $ep[$i][1]];

                    return $k1 > $k0 ? $e0 + ($e1 - $e0) * ($km - $k0) / ($k1 - $k0) : $e1;
                }
            }

            return (float) $ep[count($ep) - 1][1];
        };

        $stops = [['code' => 'START', 'type' => 'start', 'title' => 'Start', 'km' => 0.0, 'cutoff' => null]];
        foreach ($this->coursePoints() as $p) {
            if ($p['km'] > 0 && $p['km'] < $total) {
                $stops[] = $p;
            }
        }
        $stops[] = ['code' => 'FINISH', 'type' => 'finish', 'title' => 'Finish', 'km' => $total, 'cutoff' => null];

        // naik/turun & gradien maksimum antara dua titik KM (titik profil di dalamnya + kedua ujung)
        $measure = function (float $a, float $b) use ($ep, $eleAt): array {
            $samples = [[$a, $eleAt($a)]];
            foreach ($ep as $p) {
                if ((float) $p[0] > $a && (float) $p[0] < $b) {
                    $samples[] = [(float) $p[0], (float) $p[1]];
                }
            }
            $samples[] = [$b, $eleAt($b)];

            $gain = $loss = $maxGrade = 0.0;
            for ($j = 1; $j < count($samples); $j++) {
                $d = $samples[$j][1] - $samples[$j - 1][1];
                $gain += max(0, $d);
                $loss += max(0, -$d);
                $run = ($samples[$j][0] - $samples[$j - 1][0]) * 1000;
                if ($run >= 50) {
                    $maxGrade = max($maxGrade, abs($d) / $run * 100);
                }
            }

            return [$gain, $loss, $maxGrade];
        };
        // jarak-setara-datar: +1 km tiap 100 m naik, +1 km tiap 300 m turun
        $effort = fn (float $dist, float $gain, float $loss) => round($dist + $gain / 100 + $loss / 300, 3);

        $segments = [];
        for ($i = 1; $i < count($stops); $i++) {
            $a = (float) $stops[$i - 1]['km'];
            $b = (float) $stops[$i]['km'];
            $dist = max(0.0, $b - $a);
            [$gain, $loss, $maxGrade] = $measure($a, $b);

            $segments[] = [
                'from' => $stops[$i - 1]['code'],
                'to' => $stops[$i]['code'],
                'dist' => round($dist, 2),
                'gain' => (int) round($gain),
                'loss' => (int) round($loss),
                'max_grade' => (int) round($maxGrade),
                'label' => self::segmentCharacter($dist, $gain, $loss),
                'effort' => $effort($dist, $gain, $loss),
            ];
        }

        // split per KM (KM terakhir bisa parsial)
        $hazards = $this->coursePois()->where('hazard', true);
        $splits = [];
        for ($a = 0.0; $a < $total - 0.05; $a += 1.0) {
            $b = min($a + 1.0, $total);
            $dist = $b - $a;
            [$gain, $loss] = $measure($a, $b);
            $splits[] = [
                'km' => (int) round($b) === (int) ceil($b - 0.001) && $b < $total ? (int) round($b) : round($b, 1),
                'dist' => round($dist, 2),
                'gain' => (int) round($gain),
                'loss' => (int) round($loss),
                'grade' => round(($eleAt($b) - $eleAt($a)) / ($dist * 1000) * 100, 1),
                'ele' => (int) round($eleAt($b)),
                'effort' => $effort($dist, $gain, $loss),
                'hazards' => $hazards
                    ->filter(fn ($h) => $h['km'] < $b && ($h['km_end'] ?? $h['km']) >= $a)
                    ->map(fn ($h) => ['icon' => $h['icon'], 'title' => $h['title']])
                    ->values()->all(),
                'points' => collect($stops)
                    ->filter(fn ($s) => ! in_array($s['type'], ['start', 'finish']) && $s['km'] > $a && $s['km'] <= $b)
                    ->map(fn ($s) => ['code' => $s['code'], 'type' => $s['type']])
                    ->values()->all(),
            ];
        }

        return ['stops' => $stops, 'segments' => $segments, 'splits' => $splits, 'pois' => $this->coursePois()->all(), 'total_km' => round($total, 2)];
    }

    private static function segmentCharacter(float $dist, float $gain, float $loss): string
    {
        if ($dist <= 0) {
            return '-';
        }
        $up = $gain / $dist;   // m naik per km
        $down = $loss / $dist; // m turun per km

        return match (true) {
            $up >= 60 && $gain > $loss * 1.5 => 'Tanjakan berat',
            $down >= 60 && $loss > $gain * 1.5 => 'Turunan curam',
            $up >= 25 && $gain > $loss * 1.3 => 'Dominan menanjak',
            $down >= 25 && $loss > $gain * 1.3 => 'Dominan menurun',
            ($up + $down) >= 40 => 'Naik-turun',
            default => 'Relatif datar',
        };
    }

    /** Jam start (menit sejak 00:00) dari teks "06:00 WITA". */
    public function startMinutes(): ?int
    {
        return preg_match('/(\d{1,2})[:.](\d{2})/', (string) $this->start_time, $m) ? (int) $m[1] * 60 + (int) $m[2] : null;
    }

    /** Batas waktu total (menit) dari teks "4 Jam" / "4,5 jam" / "150 menit". */
    public function cutOffMinutes(): ?int
    {
        $txt = strtolower((string) $this->cut_off_time);
        if (preg_match('/([\d]+(?:[.,]\d+)?)\s*jam/', $txt, $m)) {
            $min = (float) str_replace(',', '.', $m[1]) * 60;
            if (preg_match('/(\d+)\s*menit/', $txt, $mm)) {
                $min += (int) $mm[1];
            }

            return (int) round($min);
        }

        return preg_match('/(\d+)\s*menit/', $txt, $m) ? (int) $m[1] : null;
    }

    public function getWaterStationLabelAttribute(): string
    {
        $count = $this->water_station_count;

        return $count > 0 ? $count . ' Pos' : ($this->water_station ?: '-');
    }

    public function getNormalFormattedAttribute(): string
    {
        return $this->priceFormatted($this->price_normal);
    }

    // ===== Harga efektif berbasis tanggal =====

    /** Early bird masih berlaku? (ada harga EB & belum lewat batas tanggal). */
    public function earlyBirdActive(): bool
    {
        if (! $this->price_early_bird) {
            return false;
        }

        // Tanpa batas tanggal = selalu berlaku.
        return $this->early_bird_until === null || today()->lte($this->early_bird_until);
    }

    /** Early bird sudah selesai? (ada batas tanggal & sudah lewat). */
    public function earlyBirdEnded(): bool
    {
        return $this->early_bird_until !== null
            && $this->price_early_bird
            && today()->gt($this->early_bird_until);
    }

    /** Harga yang berlaku sekarang: early bird bila aktif, selain itu normal. */
    public function currentPrice(): ?int
    {
        if ($this->earlyBirdActive()) {
            return $this->price_early_bird;
        }

        return $this->price_normal ?: $this->price_early_bird;
    }

    public function getCurrentPriceFormattedAttribute(): string
    {
        return $this->priceFormatted($this->currentPrice());
    }

    /** Label harga aktif. */
    public function currentPriceLabel(): string
    {
        return $this->earlyBirdActive() ? 'Early Bird' : 'Normal';
    }
}
