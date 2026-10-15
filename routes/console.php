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
