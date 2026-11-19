<?php

namespace App\Console\Commands;

use App\Mail\RoadToGtrReminder;
use App\Models\GtrRegistration;
use App\Models\RoadToGtrRegistration;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

/**
 * Blast H-1 Road to GTR ke peserta GTR lunas (form Road to GTR tidak punya email).
 * Peserta yang no. WA-nya ada di pendaftar Road to GTR dapat versi "sudah terdaftar".
 * Satu email sekali kirim: alamat terkirim dicatat di storage/app/road-to-gtr-reminder-sent.txt.
 */
class SendRoadToGtrReminder extends Command
{
    protected $signature = 'gtr:road-reminder
        {--dry-run : Tampilkan jumlah penerima tanpa mengirim}
        {--test= : Kirim contoh ke email ini (versi terdaftar & belum)}';

    protected $description = 'Kirim email pengingat H-1 Road to GTR (+ link daftar)';

    private const LOG = 'road-to-gtr-reminder-sent.txt';

    public function handle(): int
    {
        $phones = RoadToGtrRegistration::pluck('phone')->flip();
        $remaining = RoadToGtrRegistration::remaining();

        // Satu penerima per email; utamakan baris yang terdaftar Road to GTR.
        $recipients = GtrRegistration::where('payment_status', 'paid')
            ->whereNotNull('email')->where('email', '!=', '')
            ->orderBy('id')->get()
            ->map(fn ($r) => [$r, isset($phones[RoadToGtrRegistration::normalizePhone($r->whatsapp)])])
            ->sortByDesc(fn ($x) => $x[1])
            ->unique(fn ($x) => strtolower(trim($x[0]->email)));

        if ($email = $this->option('test')) {
            foreach ([true, false] as $registered) {
                $reg = ($recipients->first(fn ($x) => $x[1] === $registered) ?? $recipients->first())[0]->replicate();
                Mail::to($email)->send(new RoadToGtrReminder($reg, $registered, $remaining));
                $this->info('Contoh ' . ($registered ? 'terdaftar' : 'belum daftar') . " terkirim ke {$email}.");
            }

            return self::SUCCESS;
        }

        $sentLog = collect(explode("\n", (string) Storage::get(self::LOG)))->filter()->flip();
        $todo = $recipients->reject(fn ($x) => isset($sentLog[strtolower(trim($x[0]->email))]));
        $reg = $todo->filter(fn ($x) => $x[1])->count();

        if ($this->option('dry-run')) {
            $this->info("[dry-run] {$todo->count()} penerima ({$reg} terdaftar Road to GTR), sisa slot {$remaining}, sudah terkirim sebelumnya {$sentLog->count()}.");

            return self::SUCCESS;
        }

        $sent = 0;
        $failed = [];
        foreach ($todo as [$r, $registered]) {
            try {
                Mail::to(trim($r->email))->send(new RoadToGtrReminder($r, $registered, $remaining));
                Storage::append(self::LOG, strtolower(trim($r->email)));
                $sent++;
            } catch (\Throwable $e) {
                $failed[] = $r->id;
                Log::warning("Gagal kirim Road to GTR reminder #{$r->id}: " . $e->getMessage());
            }
        }

        $this->info("{$sent} dari {$todo->count()} email terkirim." . ($failed ? ' Gagal (id): ' . implode(',', $failed) : ''));

        return self::SUCCESS;
    }
}
