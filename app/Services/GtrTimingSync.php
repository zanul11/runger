<?php

namespace App\Services;

use App\Models\GtrCategory;
use App\Models\GtrScanLog;
use App\Models\GtrTimingPoint;
use App\Models\GtrTimingPointAssignment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Titik timing (pos scan BIB) dibentuk OTOMATIS dari "Titik di Rute" tiap kategori:
 *  - FINISH (race village) untuk semua kategori. TIDAK ada pos START: tidak ada scan di garis
 *    start — semua peserta mengikuti gun time kategori (lihat ResultCalculator; net time kosong).
 *  - Satu pos per LOKASI fisik. Lokasi yang dipakai beberapa kategori / dilewati dua kali
 *    (mis. Pertigaan Pos 1 Keteri = WS1 7K, WS1 & WS2 15K) menjadi SATU pos, sehingga
 *    marshal di lokasi itu cukup memegang satu pos untuk semua pelari.
 *
 * Pivot kategori ↔ pos (urutan, km, wajib, cut-off) dibangun ulang tiap sinkron. Pos disimpan
 * dengan `location_key` tetap, jadi penugasan marshal & scan log tidak putus saat sinkron ulang.
 * Pos lama buatan manual yang namanya sama dengan lokasi ikut diadopsi.
 */
class GtrTimingSync
{
    public const KEY_START = 'start';

    public const KEY_FINISH = 'finish';

    /** @return array{points: int, categories: int} */
    public static function run(): array
    {
        $categories = GtrCategory::orderBy('sort_order')->orderBy('id')->get();
        // Belum ada kategori yang punya titik pos → jangan buat apa pun (data timing manual tetap).
        if (! $categories->contains(fn (GtrCategory $c) => $c->coursePoints()->isNotEmpty())) {
            return ['points' => 0, 'categories' => 0];
        }

        return DB::transaction(function () use ($categories) {
            // 1) Kumpulkan lokasi unik dari semua kategori.
            $posts = [];
            foreach ($categories as $cat) {
                foreach ($cat->coursePoints() as $p) {
                    [$key, $name] = self::locationOf($p);
                    $posts[$key] ??= ['name' => $name, 'checkpoint' => false, 'km' => INF];
                    $posts[$key]['checkpoint'] = $posts[$key]['checkpoint'] || in_array($p['type'], ['cp', 'cop'], true);
                    $posts[$key]['km'] = min($posts[$key]['km'], (float) $p['km']);
                }
            }
            uasort($posts, fn ($a, $b) => $a['km'] <=> $b['km']);

            // 2) Upsert pos (lokasi-lokasi, FINISH). Tanpa START.
            $ids = [];
            $order = 10;
            foreach ($posts as $key => $post) {
                $type = $post['checkpoint'] ? GtrTimingPoint::TYPE_CHECKPOINT : GtrTimingPoint::TYPE_WATER_STATION;
                $ids[$key] = self::upsert($key, $post['name'], $type, $order)->id;
                $order += 10;
            }
            $ids[self::KEY_FINISH] = self::upsert(self::KEY_FINISH, 'Finish', GtrTimingPoint::TYPE_FINISH, 999)->id;

            // 3) Bangun ulang pivot tiap kategori (pos manual yang tidak terpakai ikut dilepas).
            foreach ($categories as $cat) {
                $rows = [];
                $seq = 1;
                $lastKm = 0.0;
                foreach ($cat->coursePoints()->sortBy('km') as $p) {
                    [$key] = self::locationOf($p);
                    $id = $ids[$key];
                    $mandatory = in_array($p['type'], ['cp', 'cop'], true);
                    $cutoff = $p['cutoff'] ? self::raceTime($p['cutoff']) : null;
                    $lastKm = max($lastKm, (float) $p['km']);
                    $visit = ['code' => $p['code'], 'km' => round((float) $p['km'], 2), 'cutoff' => $p['cutoff']];
                    if (isset($rows[$id])) {
                        // Lokasi dilewati lagi (mis. 15K di Pertigaan: WS1 lalu WS2): pos tetap satu,
                        // tiap lewat dicatat sebagai kunjungan (lihat App\Support\Passes).
                        $rows[$id]['is_mandatory'] = $rows[$id]['is_mandatory'] || $mandatory;
                        $rows[$id]['cutoff_at'] ??= $cutoff;
                        $rows[$id]['visits'][] = $visit;

                        continue;
                    }
                    $rows[$id] = ['sequence' => $seq++, 'km' => round((float) $p['km'], 2), 'is_mandatory' => $mandatory, 'cutoff_at' => $cutoff, 'visits' => [$visit]];
                }
                foreach ($rows as &$row) {
                    $row['visits'] = json_encode($row['visits']);
                }
                unset($row);
                $rows[$ids[self::KEY_FINISH]] = [
                    'sequence' => $seq,
                    'km' => $cat->total_km ? round((float) $cat->total_km, 2) : ($lastKm ?: null),
                    'is_mandatory' => true,
                    'cutoff_at' => self::finishCutoff($cat),
                    'visits' => json_encode([['code' => 'FINISH', 'km' => $cat->total_km ? round((float) $cat->total_km, 2) : ($lastKm ?: null), 'cutoff' => null]]),
                ];
                $cat->timingPoints()->sync($rows);
            }

            // Pos START lama (dari versi sebelumnya) sudah lepas dari semua kategori; hapus bila belum
            // pernah dipakai (tanpa scan & tanpa marshal). Bila sudah dipakai, dibiarkan untuk audit.
            GtrTimingPoint::where('location_key', self::KEY_START)
                ->whereDoesntHave('scanLogs')
                ->whereDoesntHave('assignments')
                ->delete();

            return ['points' => count($ids), 'categories' => $categories->count()];
        });
    }

    /** Kunci & nama lokasi sebuah titik pos (lokasi diisi admin; cadangan: nama / kode titik). */
    public static function locationOf(array $p): array
    {
        $name = trim((string) ($p['location'] ?? '')) ?: (trim((string) ($p['name'] ?? '')) ?: $p['code']);

        return ['loc:'.Str::slug($name), $name];
    }

    private static function upsert(string $key, string $name, string $type, int $order): GtrTimingPoint
    {
        $tp = GtrTimingPoint::where('location_key', $key)->first();
        $legacy = self::legacyMatch($key, $name, $type);

        // Pos lama buatan manual untuk lokasi yang sama: pertahankan agar penugasan marshal & scan tetap.
        if ($legacy) {
            if ($tp && ($tp->scanLogs()->exists() || $tp->assignments()->exists())) {
                GtrScanLog::where('gtr_timing_point_id', $legacy->id)->update(['gtr_timing_point_id' => $tp->id]);
                GtrTimingPointAssignment::where('gtr_timing_point_id', $legacy->id)->update(['gtr_timing_point_id' => $tp->id]);
                $legacy->delete();
            } else {
                $tp?->delete();
                $tp = $legacy;
            }
        }

        $tp ??= new GtrTimingPoint;
        $tp->fill(['location_key' => $key, 'name' => $name, 'type' => $type, 'sort_order' => $order]);
        if (! $tp->exists) {
            $tp->code = null; // kode otomatis dari tipe (START / FINISH / CP1 / WS1 …)
        }
        $tp->save();

        return $tp;
    }

    /** Pos manual lama (tanpa kunci) yang mewakili lokasi ini, mis. "CP1 - Air Terjun Melosor". */
    private static function legacyMatch(string $key, string $name, string $type): ?GtrTimingPoint
    {
        $candidates = GtrTimingPoint::whereNull('location_key')->orderBy('id')->get();
        if (in_array($key, [self::KEY_START, self::KEY_FINISH], true)) {
            return $candidates->firstWhere('type', $type);
        }
        $target = Str::slug($name);

        return $candidates->first(function (GtrTimingPoint $tp) use ($target) {
            $n = Str::slug(preg_replace('/^\s*(ws|cop|cp|pos)\s*\d*\s*[-–:·]?\s*/iu', '', (string) $tp->name));

            return $n !== '' && ($n === $target || str_contains($n, $target) || str_contains($target, $n));
        });
    }

    /** "09:00" → waktu pada hari lomba (zona aplikasi). */
    public static function raceTime(string $hm): ?Carbon
    {
        if (! preg_match('/^\s*(\d{1,2})[:.](\d{2})/', $hm, $m)) {
            return null;
        }

        return Carbon::parse(config('services.gtr.race_date', '2026-11-29').sprintf(' %02d:%02d:00', $m[1], $m[2]), 'Asia/Makassar');
    }

    /** Cut-off finish: cut_off_at kategori, atau jam start + durasi COT ("4 Jam"), atau jam COT langsung. */
    private static function finishCutoff(GtrCategory $cat): ?Carbon
    {
        if ($cat->cut_off_at) {
            return Carbon::parse($cat->cut_off_at);
        }
        $cot = trim((string) $cat->cut_off_time);
        if ($cot === '' || $cot === '-') {
            return null;
        }
        if (preg_match('/^(\d{1,2})[:.](\d{2})/', $cot)) {
            return self::raceTime($cot);
        }
        $start = self::raceTime((string) $cat->start_time);
        if ($start && preg_match('/^(\d+(?:[.,]\d+)?)\s*(jam|hours?|hrs?|h)\b/i', $cot, $m)) {
            return $start->copy()->addMinutes((int) round((float) str_replace(',', '.', $m[1]) * 60));
        }

        return null;
    }
}
