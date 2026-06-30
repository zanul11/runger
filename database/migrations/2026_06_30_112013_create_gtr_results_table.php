<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Hasil terhitung per peserta GTR. Ditulis ulang oleh `php artisan gtr:compute-results`.
        Schema::create('gtr_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gtr_registration_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('gun_time_seconds')->nullable();
            $table->unsignedInteger('net_time_seconds')->nullable();
            $table->unsignedInteger('rank_overall')->nullable();
            $table->unsignedInteger('rank_category')->nullable();
            $table->unsignedInteger('rank_gender')->nullable();
            $table->string('status')->default('finisher'); // finisher, dnf, dq, dns
            $table->dateTime('computed_at')->nullable();
            $table->timestamps();

            $table->unique('gtr_registration_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gtr_results');
    }
};
