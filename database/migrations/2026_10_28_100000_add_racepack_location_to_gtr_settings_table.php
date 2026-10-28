<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gtr_settings', function (Blueprint $table) {
            // Lokasi RPC lengkap untuk slide Race Pack Collection (seperti Race Village)
            $table->string('racepack_address')->nullable();
            $table->string('racepack_maps_url')->nullable();
            $table->text('racepack_directions')->nullable(); // satu baris = satu langkah
        });
    }

    public function down(): void
    {
        Schema::table('gtr_settings', function (Blueprint $table) {
            $table->dropColumn(['racepack_address', 'racepack_maps_url', 'racepack_directions']);
        });
    }
};
