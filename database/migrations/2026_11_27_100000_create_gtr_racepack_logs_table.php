<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Log pengambilan race pack: tiap penyerahan 1 baris, pembatalan menandai baris (tidak dihapus)
     * supaya riwayat tetap mencatat siapa membatalkan & kapan. Kolom racepack_* di registrasi
     * tetap jadi status terkini. Data yang sudah ada di-backfill.
     */
    public function up(): void
    {
        Schema::create('gtr_racepack_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gtr_registration_id')->constrained()->cascadeOnDelete();
            $table->dateTime('collected_at');
            $table->foreignId('collected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('picked_by')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index('collected_at');
        });

        DB::table('gtr_registrations')->whereNotNull('racepack_collected_at')->orderBy('id')
            ->each(fn ($r) => DB::table('gtr_racepack_logs')->insert([
                'gtr_registration_id' => $r->id,
                'collected_at' => $r->racepack_collected_at,
                'collected_by' => $r->racepack_collected_by,
                'picked_by' => $r->racepack_picked_by,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
    }

    public function down(): void
    {
        Schema::dropIfExists('gtr_racepack_logs');
    }
};
