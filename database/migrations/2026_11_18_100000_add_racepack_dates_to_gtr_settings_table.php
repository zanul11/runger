<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gtr_settings', function (Blueprint $table) {
            // Rentang tanggal RPC (racepack_schedule tetap teks bebas untuk tampilan). Dipakai kartu
            // "Race Day" di Runger App untuk tahu kapan mode pengambilan race pack aktif.
            $table->date('racepack_starts_on')->nullable()->after('racepack_schedule');
            $table->date('racepack_ends_on')->nullable()->after('racepack_starts_on');
        });
    }

    public function down(): void
    {
        Schema::table('gtr_settings', function (Blueprint $table) {
            $table->dropColumn(['racepack_starts_on', 'racepack_ends_on']);
        });
    }
};
