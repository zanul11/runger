<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gtr_settings', function (Blueprint $table) {
            // Batas akhir pendaftaran online (WITA); null = selalu buka
            $table->dateTime('registration_closes_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('gtr_settings', function (Blueprint $table) {
            $table->dropColumn('registration_closes_at');
        });
    }
};
