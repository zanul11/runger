<?php

namespace App\Console\Commands;

use App\Support\GtrEventSummary;
use Illuminate\Console\Command;

/** Ringkasan data GTR (angka gabungan, tanpa data pribadi) untuk presentasi & laporan. */
class GtrSummary extends Command
{
    protected $signature = 'gtr:summary {--json : Keluaran JSON}';

    protected $description = 'Ringkasan peserta GTR: jumlah, kategori, asal daerah, usia, komunitas (tanpa data pribadi)';

    public function handle(): int
    {
        $s = GtrEventSummary::build();
        $this->line($this->option('json') ? json_encode($s, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : GtrEventSummary::text($s));

        return self::SUCCESS;
    }
}
