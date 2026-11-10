<?php

namespace App\Jobs;

use App\Services\ResultCalculator;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Cache;

/**
 * Hitung ulang hasil kategori setelah batch scan masuk / start ditembak.
 *
 * Dijalankan dengan dispatch(...)->afterResponse(): berjalan di proses PHP yang sama
 * SETELAH response terkirim ke scanner, jadi tidak perlu queue worker / cron.
 *
 * Debounce (coalescing) per kategori: tiap permintaan hanya menandai kategori "dirty".
 * Satu proses yang memegang lock menghitung berulang selama masih dirty; permintaan lain
 * yang datang saat itu cukup menandai dirty lalu selesai. Jadi saat banyak device sync
 * bersamaan, perhitungan tidak dobel tapi scan terakhir tetap terhitung.
 */
class RecomputeGtrResults
{
    use Dispatchable;

    /** @param  list<int>  $categoryIds */
    public function __construct(public array $categoryIds) {}

    public function handle(ResultCalculator $calculator): void
    {
        foreach (array_unique(array_map('intval', $this->categoryIds)) as $id) {
            Cache::put(self::dirtyKey($id), true, 600);
            self::drain($id, $calculator);
        }
    }

    private static function drain(int $id, ResultCalculator $calculator): void
    {
        // Ulang bila flag dirty muncul tepat setelah lock dilepas.
        for ($attempt = 0; $attempt < 3 && Cache::has(self::dirtyKey($id)); $attempt++) {
            $lock = Cache::lock('gtr:results:lock:'.$id, 120);
            if (! $lock->get()) {
                return; // proses lain sedang menghitung dan akan melihat flag dirty
            }

            try {
                for ($i = 0; $i < 10 && Cache::pull(self::dirtyKey($id)); $i++) {
                    $calculator->recomputeCategory($id);
                }
            } finally {
                $lock->release();
            }
        }
    }

    private static function dirtyKey(int $id): string
    {
        return 'gtr:results:dirty:'.$id;
    }
}
