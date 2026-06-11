<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gtr_categories', function (Blueprint $table) {
            $table->decimal('total_km', 6, 2)->nullable()->after('route_points');
            $table->json('elevation_profile')->nullable()->after('total_km'); // [[km, ele], ...]
            $table->json('water_stations')->nullable()->after('water_station'); // [{km, name}, ...]
        });
    }

    public function down(): void
    {
        Schema::table('gtr_categories', function (Blueprint $table) {
            $table->dropColumn(['total_km', 'elevation_profile', 'water_stations']);
        });
    }
};
