<?php

namespace App\Console\Commands;

use App\Models\GtrRegistration;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Email pengingat race day otomatis:
 *  - H-7 : pengingat ambil race pack (peserta lunas yang belum ambil)
 *  - H-1 : briefing race day (race plan, cuaca, gear, jam kumpul)
 * Mode `auto` (default, dijadwalkan harian) hanya mengirim pada hari yang tepat.
 * Tiap peserta hanya dikirimi sekali per jenis (kolom *_sent_at).
 */
class SendGtrRaceReminders extends Command
{
    protected $signature = 'gtr:race-reminders
        {type=auto : auto | h7 | h1}
        {--dry-run : Tampilkan jumlah penerima tanpa mengirim}
        {--test= : Kirim satu contoh ke email ini (tidak menandai terkirim)}';

    protected $description = 'Kirim email pengingat race pack (H-7) & briefing race day (H-1) GTR';

    public function handle(): int
    {
        $raceDate = Carbon::parse(config('services.gtr.race_date'), 'Asia/Makassar')->startOfDay();
        $daysLeft = (int) now('Asia/Makassar')->startOfDay()->diffInDays($raceDate, false);

        $type = $this->argument('type');
        if ($type === 'auto') {
            $type = match ($daysLeft) {
                7 => 'h7',
                1 => 'h1',
                default => null,
            };
            if (! $type) {
                $this->info("Bukan H-7/H-1 (H-{$daysLeft}). Tidak ada email dikirim.");

                return self::SUCCESS;
            }
        }
        if (! in_array($type, ['h7', 'h1'], true)) {
            $this->error('Tipe harus auto, h7, atau h1.');

            return self::INVALID;
        }

        $column = $type === 'h7' ? 'h7_reminder_sent_at' : 'briefing_sent_at';
        $query = GtrRegistration::with('category')
            ->where('payment_status', 'paid')
            ->whereNotNull('email')
            ->whereNull($column)
            ->when($type === 'h7', fn ($q) => $q->whereNull('racepack_collected_at'));

        if ($email = $this->option('test')) {
            $reg = (clone $query)->first() ?? GtrRegistration::with('category')->where('payment_status', 'paid')->first();
            if (! $reg) {
                $this->error('Tidak ada peserta lunas untuk contoh.');

                return self::FAILURE;
            }
            $reg->email = $email; // hanya di memori, tidak disimpan
            $ok = $type === 'h7' ? $reg->sendRacepackReminder() : $reg->sendRaceBriefing();
            $this->info($ok ? "Contoh {$type} terkirim ke {$email}." : 'Gagal mengirim contoh (cek log mail).');

            return $ok ? self::SUCCESS : self::FAILURE;
        }

        $total = (clone $query)->count();
        if ($this->option('dry-run')) {
            $this->info("[dry-run] {$type}: {$total} penerima.");

            return self::SUCCESS;
        }

        $sent = 0;
        $query->chunkById(50, function ($regs) use ($type, $column, &$sent) {
            foreach ($regs as $reg) {
                $ok = $type === 'h7' ? $reg->sendRacepackReminder() : $reg->sendRaceBriefing();
                if ($ok) {
                    $reg->forceFill([$column => now()])->saveQuietly();
                    $sent++;
                }
            }
        });

        $this->info("{$type}: {$sent} dari {$total} email terkirim.");

        return self::SUCCESS;
    }
}
