<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('routes', function (Blueprint $table) {
            $table->json('elevation_profile')->nullable()->after('km_markers');
            $table->integer('elevation_min_m')->nullable()->after('elevation_gain_m');
            $table->integer('elevation_max_m')->nullable()->after('elevation_min_m');
        });
    }

    public function down(): void
    {
        Schema::table('routes', function (Blueprint $table) {
            $table->dropColumn(['elevation_profile', 'elevation_min_m', 'elevation_max_m']);
        });
    }
};
