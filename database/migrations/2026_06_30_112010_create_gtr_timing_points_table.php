<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gtr_timing_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('code');                       // START, CP1, WS1, FINISH
            $table->string('name');                       // "Start / Finish Bukit Keteri"
            $table->enum('type', ['start', 'checkpoint', 'water_station', 'finish']);
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['event_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gtr_timing_points');
    }
};
