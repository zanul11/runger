<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\GtrCategory;
use App\Models\GtrTimingPoint;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Seeder modul RACE-TIMING untuk Gerung Trail Run (GTR) 2026.
 *
 * Membuat (idempoten):
 *  - 1 event "Gerung Trail Run 2026" (di tabel events yang sudah ada)
 *  - 5 timing point: START, CP1, WS1, WS2, FINISH
 *  - pivot kategori<->titik:
 *      7K  = START, CP1, WS1, FINISH
 *      15K = START, CP1, WS1, WS2, FINISH
 *  - gun_start & cut_off contoh per kategori
 *
 * Koordinat & jam hanyalah CONTOH (sekitar Bukit Keteri, Gerung) — mudah diubah
 * lewat admin Filament nanti.
 */
class GtrTimingSeeder extends Seeder
{
    public function run(): void
    {
        // Pastikan kategori GTR ada lebih dulu.
        if (GtrCategory::query()->whereIn('slug', ['7k', '15k'])->count() < 2) {
            $this->call(GtrCategorySeeder::class);
        }

        // Tanggal lomba: Minggu, 29 November 2026 (WITA / Asia/Makassar).
        $raceDate = '2026-11-29';
        $tz = 'Asia/Makassar';

        $event = Event::updateOrCreate(
            ['slug' => 'gerung-trail-run-2026'],
            [
                'title' => 'Gerung Trail Run 2026',
                'subtitle' => '1st Edition · Bukit Keteri Trail',
                'tag' => 'GTR 2026',
                'date' => $raceDate,
                'time' => '05:30',
                'default_gun_start' => Carbon::parse("$raceDate 05:30", $tz),
                'distance_text' => '7K · 15K',
                'location' => 'Bukit Keteri, Gerung — Lombok Barat',
                'tikum' => 'Bukit Keteri, Gerung',
                'tikum_lat' => -8.690000,
                'tikum_lng' => 116.130000,
                'is_published' => true,
            ],
        );

        // --- Timing points (contoh koordinat) ---
        $points = [
            ['code' => 'START',  'name' => 'Start · Bukit Keteri',      'type' => GtrTimingPoint::TYPE_START,         'latitude' => -8.690000, 'longitude' => 116.130000, 'sort_order' => 1],
            ['code' => 'CP1',    'name' => 'Checkpoint 1 · Punggungan', 'type' => GtrTimingPoint::TYPE_CHECKPOINT,    'latitude' => -8.697000, 'longitude' => 116.138000, 'sort_order' => 2],
            ['code' => 'WS1',    'name' => 'Water Station 1',           'type' => GtrTimingPoint::TYPE_WATER_STATION, 'latitude' => -8.701000, 'longitude' => 116.142000, 'sort_order' => 3],
            ['code' => 'WS2',    'name' => 'Water Station 2',           'type' => GtrTimingPoint::TYPE_WATER_STATION, 'latitude' => -8.708000, 'longitude' => 116.149000, 'sort_order' => 4],
            ['code' => 'FINISH', 'name' => 'Finish · Bukit Keteri',     'type' => GtrTimingPoint::TYPE_FINISH,        'latitude' => -8.690000, 'longitude' => 116.130000, 'sort_order' => 5],
        ];

        $tp = [];
        foreach ($points as $p) {
            $tp[$p['code']] = GtrTimingPoint::updateOrCreate(
                ['event_id' => $event->id, 'code' => $p['code']],
                $p,
            );
        }

        // --- Kategori: jam start + cut off contoh ---
        $cat7 = GtrCategory::where('slug', '7k')->first();
        $cat15 = GtrCategory::where('slug', '15k')->first();

        if ($cat7) {
            $cat7->forceFill([
                'gun_start' => Carbon::parse("$raceDate 06:00", $tz),
                'cut_off_at' => Carbon::parse("$raceDate 08:00", $tz), // COT 2 jam
            ])->save();
        }
        if ($cat15) {
            $cat15->forceFill([
                'gun_start' => Carbon::parse("$raceDate 05:30", $tz),
                'cut_off_at' => Carbon::parse("$raceDate 09:30", $tz), // COT 4 jam
            ])->save();
        }

        // --- Pivot kategori <-> titik (sequence, is_mandatory, cutoff_at) ---
        // Water station = lewat tapi tidak wajib scan; start/checkpoint/finish = wajib.
        if ($cat7) {
            $cat7->timingPoints()->syncWithoutDetaching([
                $tp['START']->id  => ['sequence' => 1, 'is_mandatory' => true],
                $tp['CP1']->id    => ['sequence' => 2, 'is_mandatory' => true],
                $tp['WS1']->id    => ['sequence' => 3, 'is_mandatory' => false],
                $tp['FINISH']->id => ['sequence' => 4, 'is_mandatory' => true, 'cutoff_at' => $cat7->cut_off_at],
            ]);
        }

        if ($cat15) {
            $cat15->timingPoints()->syncWithoutDetaching([
                $tp['START']->id  => ['sequence' => 1, 'is_mandatory' => true],
                $tp['CP1']->id    => ['sequence' => 2, 'is_mandatory' => true],
                $tp['WS1']->id    => ['sequence' => 3, 'is_mandatory' => false],
                $tp['WS2']->id    => ['sequence' => 4, 'is_mandatory' => false],
                $tp['FINISH']->id => ['sequence' => 5, 'is_mandatory' => true, 'cutoff_at' => $cat15->cut_off_at],
            ]);
        }

        $this->command?->info('GTR timing seeded: event #' . $event->id . ', ' . count($tp) . ' timing points.');
    }
}
