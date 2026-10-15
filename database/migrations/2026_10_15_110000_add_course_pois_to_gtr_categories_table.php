<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gtr_categories', function (Blueprint $table) {
            // Info & bahaya jalur (licin, sungai, medis, marshal, spot foto, ...) — tampil di Course Map
            $table->json('course_pois')->nullable()->after('water_stations');
        });
    }

    public function down(): void
    {
        Schema::table('gtr_categories', function (Blueprint $table) {
            $table->dropColumn('course_pois');
        });
    }
};
