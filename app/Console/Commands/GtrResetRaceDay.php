<?php

namespace App\Console\Commands;

use App\Casts\DateTimeMs;
use App\Models\GtrSetting;
use App\Services\GtrRaceDayReset;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Hapus data UJI COBA race day supaya sistem kembali bersih sebelum race sungguhan.
 * Detail apa yang dihapus / tidak: App\Services\GtrRaceDayReset. Versi aplikasi: menu
 * Kontrol → Reset data uji di scanner (admin, dengan kode GTR_RESET_CODE).
 */
class GtrResetRaceDay extends Command
{
    protected $signature = 'gtr:reset-race-day
        {--racepack : Ikut hapus check-in racepack (pengambilan racepack kembali "belum ambil")}
        {--logout : Logout semua HP scanner (marshal & racepack harus login ulang)}
        {--force : Lewati konfirmasi (untuk dijalankan tanpa interaksi)}';

    protected $description = 'Hapus data uji race day GTR (scan, hasil, gun time; opsional racepack & login HP) sebelum race sungguhan.';

    public const CONFIRM_WORD = 'RESET';

    public function handle(GtrRaceDayReset $reset): int
    {
        $racepack = (bool) $this->option('racepack');
        $logout = (bool) $this->option('logout');

        $counts = $reset->counts($racepack, $logout);
        $this->table(['Data', 'Jumlah'], collect($counts)->map(fn ($n, $label) => [$label, $n])->values()->all());

        if (GtrSetting::first()?->results_published) {
            $this->warn('Hasil sedang DIPUBLIKASIKAN — akan dimatikan.');
        }
        if (! $racepack) {
            $this->line('Check-in racepack TIDAK disentuh (pakai --racepack untuk ikut menghapus).');
        }

        if (! $this->option('force')) {
            $this->warn('Data di atas akan DIHAPUS dari database '.config('database.connections.'.config('database.default').'.database').' ('.app()->environment().').');
            if ($this->ask('Ketik '.self::CONFIRM_WORD.' untuk melanjutkan') !== self::CONFIRM_WORD) {
                $this->info('Dibatalkan. Tidak ada yang diubah.');

                return self::FAILURE;
            }
        }

        $done = $reset->run($racepack, $logout);

        $this->line('Cadangan: '.Storage::disk('local')->path($done['backup']));
        $this->info('Data uji race day dihapus. Jam reset: '.$done['reset_at']->format(DateTimeMs::FORMAT).' WITA.');
        $this->line('Scan / check-in dari antrean HP yang dibuat sebelum jam ini akan ditolak server.');
        $this->line($logout
            ? 'Semua HP scanner sudah logout: login ulang di tiap HP (antrean lama terhapus otomatis).'
            : 'HP scanner membersihkan antrean lamanya saat "Unduh ulang data peserta" / sinkron roster berikutnya (≤ 5 menit).');

        return self::SUCCESS;
    }
}
