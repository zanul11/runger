<?php

namespace App\Console\Commands;

use App\Services\GtrTimingSync;
use Illuminate\Console\Command;

class SyncGtrTiming extends Command
{
    protected $signature = 'gtr:sync-timing';

    protected $description = 'Bentuk ulang titik timing (pos scan BIB) dari "Titik di Rute" tiap kategori GTR';

    public function handle(): int
    {
        $r = GtrTimingSync::run();
        $this->info($r['points'] ? "Sinkron: {$r['points']} pos untuk {$r['categories']} kategori." : 'Belum ada kategori dengan titik pos — tidak ada yang disinkron.');

        return self::SUCCESS;
    }
}
