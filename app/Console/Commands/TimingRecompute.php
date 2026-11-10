<?php

namespace App\Console\Commands;

use App\Models\GtrCategory;
use App\Services\ResultCalculator;
use Illuminate\Console\Command;

class TimingRecompute extends Command
{
    protected $signature = 'timing:recompute {--category=* : ID atau slug kategori (kosong = semua)}';

    protected $description = 'Hitung ulang hasil GTR (status, gun/net time, ranking) dari scan log.';

    public function handle(ResultCalculator $calculator): int
    {
        $keys = $this->option('category');

        if ($keys === []) {
            $summary = $calculator->recomputeAll();
            $this->table(['Finisher', 'DNF', 'DQ', 'DNS', 'Perlu review'], [array_values($summary)]);

            return self::SUCCESS;
        }

        $rows = [];
        foreach ($keys as $key) {
            $cat = GtrCategory::where('id', $key)->orWhere('slug', $key)->first();
            if (! $cat) {
                $this->error("Kategori '$key' tidak ditemukan.");

                return self::FAILURE;
            }
            $rows[] = [$cat->name, ...array_values($calculator->recomputeCategory($cat))];
        }

        $this->table(['Kategori', 'Finisher', 'DNF', 'DQ', 'DNS', 'Perlu review'], $rows);

        return self::SUCCESS;
    }
}
