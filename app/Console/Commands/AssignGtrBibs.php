<?php

namespace App\Console\Commands;

use App\Models\GtrRegistration;
use App\Services\BibNumberService;
use Illuminate\Console\Command;

class AssignGtrBibs extends Command
{
    protected $signature = 'gtr:assign-bibs';

    protected $description = 'Beri nomor BIB ke peserta yang sudah LUNAS tapi belum punya BIB (urut waktu bayar).';

    public function handle(BibNumberService $bibs): int
    {
        $pending = GtrRegistration::where('payment_status', 'paid')
            ->whereNull('bib_number')
            ->orderByRaw('COALESCE(paid_at, created_at)')
            ->orderBy('id')
            ->get();

        if ($pending->isEmpty()) {
            $this->info('Tidak ada peserta lunas tanpa BIB.');

            return self::SUCCESS;
        }

        $count = 0;
        foreach ($pending as $reg) {
            $bib = $bibs->assignFor($reg);
            if ($bib) {
                $count++;
                $this->line("  {$reg->nomor_registrasi} · {$reg->full_name} → BIB {$bib}");
            }
        }

        $this->info("Selesai. {$count} BIB diberikan.");

        return self::SUCCESS;
    }
}
