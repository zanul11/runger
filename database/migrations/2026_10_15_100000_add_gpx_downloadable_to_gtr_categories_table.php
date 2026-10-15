<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gtr_categories', function (Blueprint $table) {
            // Boleh tidaknya peserta mengunduh file GPX dari halaman kategori
            $table->boolean('gpx_downloadable')->default(true)->after('gpx_file');
        });
    }

    public function down(): void
    {
        Schema::table('gtr_categories', function (Blueprint $table) {
            $table->dropColumn('gpx_downloadable');
        });
    }
};
