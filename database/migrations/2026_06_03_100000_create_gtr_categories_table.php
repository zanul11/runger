<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gtr_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');                       // Keteri Trail Run
            $table->string('slug')->unique();             // 7k
            $table->string('distance');                   // "7 KM"
            $table->string('tag')->nullable();            // "7K Trail"
            $table->string('color')->default('#E53935');  // dot / accent
            $table->text('description')->nullable();

            $table->string('header_image')->nullable();   // public-disk path
            $table->string('gpx_file')->nullable();       // public-disk path
            $table->json('route_points')->nullable();     // parsed from gpx -> course map

            $table->unsignedInteger('price_early_bird')->nullable();
            $table->unsignedInteger('price_normal')->nullable();
            $table->date('early_bird_until')->nullable(); // batas early bird

            $table->string('start_time')->nullable();     // "06:00 WITA"
            $table->string('cut_off_time')->nullable();   // "2 Jam"
            $table->string('elevation_gain')->nullable(); // "+344 M"
            $table->string('water_station')->nullable();  // "2 Pos"
            $table->text('award_prize')->nullable();

            $table->json('mandatory_gear')->nullable();   // ["...", "..."]
            $table->json('rundown')->nullable();          // [{time, activity}]

            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gtr_categories');
    }
};
