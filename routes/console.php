<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Email pengingat GTR: otomatis kirim H-7 (race pack) & H-1 (briefing) tiap pagi
\Illuminate\Support\Facades\Schedule::command('gtr:race-reminders')
    ->dailyAt('08:00')
    ->timezone('Asia/Makassar')
    ->withoutOverlapping();

// Blast race pack + dokumen wajib RPC dimajukan: sekali saja, Jumat 20 Nov 2026 10:00 WITA.
// Yang terkirim ditandai h7_reminder_sent_at, jadi jadwal H-7 otomatis (22 Nov) tidak mengirim ulang.
\Illuminate\Support\Facades\Schedule::command('gtr:race-reminders h7')
    ->dailyAt('10:00')
    ->timezone('Asia/Makassar')
    ->when(fn () => now('Asia/Makassar')->toDateString() === '2026-11-20')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/gtr-rpc-blast.log'));
