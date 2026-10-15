<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gtr_registrations', function (Blueprint $table) {
            // Pengambilan race pack (check-in QR)
            $table->dateTime('racepack_collected_at')->nullable()->after('race_plan_mode');
            $table->foreignId('racepack_collected_by')->nullable()->after('racepack_collected_at')->constrained('users')->nullOnDelete();
            $table->string('racepack_picked_by')->nullable()->after('racepack_collected_by'); // nama bila diwakilkan
            // Penanda email pengingat sudah terkirim (anti dobel)
            $table->dateTime('h7_reminder_sent_at')->nullable()->after('racepack_picked_by');
            $table->dateTime('briefing_sent_at')->nullable()->after('h7_reminder_sent_at');
        });

        Schema::table('gtr_settings', function (Blueprint $table) {
            $table->string('racepack_location')->nullable();
            $table->string('racepack_schedule')->nullable();
            $table->text('racepack_note')->nullable();
            $table->string('assembly_time')->nullable();          // jam kumpul race day
            $table->boolean('results_published')->default(false); // hasil resmi boleh tampil publik
        });

        Schema::create('gtr_photos', function (Blueprint $table) {
            $table->id();
            $table->string('path');
            $table->json('bibs')->nullable();          // daftar nomor BIB yang ada di foto
            $table->string('photographer')->nullable();
            $table->string('spot')->nullable();        // lokasi foto, mis. "CP1" / "Finish"
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gtr_photos');
        Schema::table('gtr_settings', function (Blueprint $table) {
            $table->dropColumn(['racepack_location', 'racepack_schedule', 'racepack_note', 'assembly_time', 'results_published']);
        });
        Schema::table('gtr_registrations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('racepack_collected_by');
            $table->dropColumn(['racepack_collected_at', 'racepack_picked_by', 'h7_reminder_sent_at', 'briefing_sent_at']);
        });
    }
};
