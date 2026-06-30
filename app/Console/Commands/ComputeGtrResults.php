<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Services\ResultEngine;
use Illuminate\Console\Command;

class ComputeGtrResults extends Command
{
    protected $signature = 'gtr:compute-results {event : ID atau slug event}';

    protected $description = 'Hitung ulang hasil GTR (net/gun time, status, ranking) dari scan logs.';

    public function handle(ResultEngine $engine): int
    {
        $key = $this->argument('event');

        $event = is_numeric($key)
            ? Event::find($key)
            : Event::where('slug', $key)->first();

        if (! $event) {
            $this->error("Event '$key' tidak ditemukan.");

            return self::FAILURE;
        }

        $this->info("Menghitung hasil untuk: {$event->title} (#{$event->id})");

        $summary = $engine->compute($event);

        $this->table(
            ['Finisher', 'DNF', 'DQ', 'DNS'],
            [[$summary['finisher'], $summary['dnf'], $summary['dq'], $summary['dns']]],
        );

        $this->info('Selesai. Hasil ditulis ke tabel gtr_results.');

        return self::SUCCESS;
    }
}
