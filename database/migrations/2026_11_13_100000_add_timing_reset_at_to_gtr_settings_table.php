<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gtr_settings', function (Blueprint $table) {
            // Kapan data uji race day terakhir dihapus (gtr:reset-race-day). Scan / check-in
            // racepack dengan waktu sebelum ini ditolak, dan scanner menghapus antrean lokal lamanya.
            $table->dateTime('timing_reset_at', 3)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('gtr_settings', function (Blueprint $table) {
            $table->dropColumn('timing_reset_at');
        });
    }
};
