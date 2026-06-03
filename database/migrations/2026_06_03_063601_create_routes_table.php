<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('gpx_file')->nullable(); // path under public/storage
            $table->decimal('total_km', 6, 2)->nullable();
            $table->integer('elevation_gain_m')->nullable();
            $table->integer('km_marker_count')->nullable();
            $table->decimal('tikum_lat', 10, 7)->nullable();
            $table->decimal('tikum_lng', 10, 7)->nullable();
            $table->json('route_points')->nullable(); // simplified [[lat,lng],...]
            $table->json('km_markers')->nullable();   // [{km,lat,lon}]
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('routes');
    }
};
