<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('race_category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('participant_id')->constrained()->cascadeOnDelete();
            $table->string('chip_time')->nullable(); // HH:MM:SS
            $table->string('gun_time')->nullable();
            $table->integer('position_overall')->nullable();
            $table->integer('position_category')->nullable();
            $table->integer('position_gender')->nullable();
            $table->string('status')->default('finisher'); // finisher, dnf, dq, dns
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('results');
    }
};
