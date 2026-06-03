<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->string('tag')->nullable();
            $table->date('date');
            $table->time('time')->default('05:30');
            $table->string('status')->default('upcoming'); // upcoming, coming_soon, completed
            $table->boolean('is_featured')->default(false);
            $table->string('distance_text')->nullable(); // "10K" or "5K · 12K · 21K"
            $table->string('location')->nullable();
            $table->string('tikum')->nullable();
            $table->decimal('tikum_lat', 10, 7)->nullable();
            $table->decimal('tikum_lng', 10, 7)->nullable();
            $table->string('briefing')->nullable();
            $table->string('pace')->nullable();
            $table->string('fee')->nullable();
            $table->text('note')->nullable();
            $table->text('description')->nullable();
            $table->string('hero_image')->nullable();
            $table->string('poster_image')->nullable();
            $table->string('hero_video')->nullable();
            $table->string('detail_template')->default('none'); // none, long_run, trail_run
            $table->string('detail_url')->nullable();
            $table->string('cta_primary_label')->nullable();
            $table->string('cta_primary_href')->nullable();
            $table->string('cta_ghost_label')->nullable();
            $table->string('cta_ghost_href')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
