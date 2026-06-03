<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('race_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('code')->nullable(); // e.g. 5K, 12K, 21K
            $table->string('name'); // "Sukawana Trail Run"
            $table->decimal('distance_km', 6, 2);
            $table->integer('difficulty_level')->default(0); // 0=easy 1=mid 2=hard
            $table->time('start_time')->nullable();
            $table->string('duration')->nullable();
            $table->string('elevation_gain')->nullable();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->string('cut_off')->nullable();
            $table->string('age_minimum')->nullable();
            $table->integer('quota')->nullable();
            $table->decimal('fee', 12, 2)->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('race_categories');
    }
};
