<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gtr_categories', function (Blueprint $table) {
            // Waktu start AKTUAL saat pistol ditembak (resmi untuk gun time). `gun_start` = jadwal.
            $table->dateTime('gun_fired_at', 3)->nullable()->after('gun_start');
            $table->foreignId('gun_fired_by')->nullable()->after('gun_fired_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('gtr_categories', function (Blueprint $table) {
            $table->dropForeign(['gun_fired_by']);
            $table->dropColumn(['gun_fired_at', 'gun_fired_by']);
        });
    }
};
