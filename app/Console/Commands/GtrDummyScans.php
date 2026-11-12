<?php

namespace App\Console\Commands;

use App\Casts\DateTimeMs;
use App\Models\GtrCategory;
use App\Models\GtrRegistration;
use App\Models\GtrScanLog;
use App\Models\GtrSetting;
use App\Models\GtrTimingPointAssignment;
use App\Services\ResultCalculator;
use App\Support\Passes;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Data DUMMY untuk uji halaman hasil: scan BIB di semua pos untuk semua peserta race-eligible.
 *
 * Waktu tiap pos = gun time resmi kategori + KM pos × pace acak per peserta, jadi urutan pos,
 * split, dan peringkat masuk akal. Baris dummy ditandai device_id = DEVICE supaya bisa dihapus
 * lagi (--clear) tanpa menyentuh scan asli. Tidak bisa dijalankan di production.
 */
class GtrDummyScans extends Command
{
    public const DEVICE = 'dummy-seed';

    protected $signature = 'gtr:dummy-scans
        {--clear : Hapus scan dummy lalu hitung ulang hasil (tanpa membuat yang baru)}
        {--dnf=0 : Persen peserta yang berhenti di tengah rute (tanpa scan finish)}
        {--dns=0 : Persen peserta tanpa scan sama sekali}
        {--publish : Nyalakan results_published supaya halaman Results menampilkan hasil}
        {--seed= : Seed angka acak (hasil bisa diulang)}';

    protected $description = 'Isi scan BIB dummy di semua pos untuk semua peserta GTR (uji halaman hasil). Khusus non-production.';

    /** Rentang pace acak (menit/km) per peserta. */
    private const PACE_MIN = 7.0;

    private const PACE_MAX = 15.0;

    public function handle(ResultCalculator $calculator): int
    {
        if (app()->environment('production')) {
            $this->error('Ditolak: command ini tidak boleh dijalankan di production.');

            return self::FAILURE;
        }

        if ($this->option('seed') !== null) {
            mt_srand((int) $this->option('seed'));
        }

        $deleted = GtrScanLog::where('device_id', self::DEVICE)->delete();
        if ($deleted) {
            $this->line("Scan dummy lama dihapus: $deleted baris.");
        }

        $rows = $this->option('clear') ? [] : $this->generate();
        foreach (array_chunk($rows, 500) as $chunk) {
            GtrScanLog::insert($chunk);
        }

        if ($this->option('publish') && ! $this->option('clear')) {
            GtrSetting::first()?->update(['results_published' => true]);
        }

        $summary = $calculator->recomputeAll();
        Cache::forget('gtr:results:public');
        Cache::forget(\App\Http\Controllers\GtrResultCheckController::CACHE_KEY);

        $this->info(count($rows).' scan dummy dibuat.');
        $this->table(['Finisher', 'DNF', 'DQ', 'DNS', 'Perlu review'], [array_values($summary)]);
        if (! GtrSetting::first()?->results_published) {
            $this->warn('results_published masih OFF — halaman Results publik kosong. Jalankan ulang dengan --publish atau nyalakan di admin.');
        }

        return self::SUCCESS;
    }

    /** @return list<array<string,mixed>> baris gtr_scan_logs siap insert */
    private function generate(): array
    {
        $marshals = GtrTimingPointAssignment::where('is_active', true)->pluck('user_id', 'gtr_timing_point_id');
        $dnfPct = (float) $this->option('dnf');
        $dnsPct = (float) $this->option('dns');
        $now = now()->format('Y-m-d H:i:s');
        $rows = [];

        foreach (GtrCategory::orderBy('sort_order')->orderBy('id')->get() as $category) {
            $gunMs = DateTimeMs::toMs($category->officialGunTime());
            $stops = $this->stops($category);
            if ($gunMs === null || $stops === []) {
                $this->warn("Kategori {$category->name} dilewati (belum ada gun time / pos timing).");

                continue;
            }

            $regs = GtrRegistration::query()->raceEligible()->where('gtr_category_id', $category->id)->orderBy('id')->get(['id', 'bib_number']);
            foreach ($regs as $reg) {
                $roll = mt_rand(0, 9999) / 100;
                if ($roll < $dnsPct) {
                    continue;
                }
                // DNF: berhenti setelah 1..n-1 pos (tidak pernah sampai pos terakhir = finish).
                $reach = $roll < $dnsPct + $dnfPct && count($stops) > 1 ? mt_rand(1, count($stops) - 1) : count($stops);

                $paceMs = self::rand(self::PACE_MIN, self::PACE_MAX) * 60_000;
                $t = $gunMs + mt_rand(0, 45_000); // jeda melewati garis start
                $prevKm = 0.0;

                foreach (array_slice($stops, 0, $reach) as $stop) {
                    // Pace tiap segmen bervariasi ±12% supaya split tidak seragam.
                    $t += (int) round(($stop['km'] - $prevKm) * $paceMs * self::rand(0.88, 1.12));
                    $prevKm = $stop['km'];
                    $at = DateTimeMs::toDb($t);

                    $rows[] = [
                        'client_uuid' => (string) Str::uuid(),
                        'gtr_registration_id' => $reg->id,
                        'gtr_timing_point_id' => $stop['point_id'],
                        'bib_input' => (string) $reg->bib_number,
                        'scanned_by' => $marshals[$stop['point_id']] ?? null,
                        'device_id' => self::DEVICE,
                        'source' => mt_rand(1, 100) <= 85 ? GtrScanLog::SOURCE_QR : GtrScanLog::SOURCE_MANUAL,
                        'raw_device_time' => $at,
                        'clock_offset_ms' => 0,
                        'scanned_at' => $at,
                        'server_received_at' => DateTimeMs::toDb($t + mt_rand(300, 3000)),
                        'is_flagged' => false,
                        'is_ignored' => false,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
        }

        return $rows;
    }

    /**
     * Semua kunjungan pos satu kategori, urut KM (pos yang dilewati 2× = 2 kunjungan).
     *
     * @return list<array{point_id:int,code:string,km:float}>
     */
    private function stops(GtrCategory $category): array
    {
        $points = $category->timingPoints()->get();
        $total = (float) $category->total_km ?: (float) $points->count();
        $stops = [];

        foreach ($points as $i => $p) {
            foreach (Passes::visits($p->pivot->visits, $p->code, $p->pivot->km) as $v) {
                $stops[] = [
                    'point_id' => $p->id,
                    'code' => $v['code'],
                    // Tanpa KM → perkirakan dari urutan pos.
                    'km' => $v['km'] ?? round($total * ($i + 1) / $points->count(), 2),
                ];
            }
        }
        usort($stops, fn ($a, $b) => $a['km'] <=> $b['km']);

        return $stops;
    }

    private static function rand(float $min, float $max): float
    {
        return $min + mt_rand() / mt_getrandmax() * ($max - $min);
    }
}
