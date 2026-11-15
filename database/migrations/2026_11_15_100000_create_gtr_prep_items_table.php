<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Checklist persiapan panitia GTR (perlengkapan, pos, medis, racepack, dll) — dikelola
        // admin dan dicentang panitia dari aplikasi GTR Crew.
        Schema::create('gtr_prep_items', function (Blueprint $table) {
            $table->id();
            $table->string('category', 64);
            $table->string('title');
            $table->string('qty', 64)->nullable();        // mis. "20 dus", "1 per pos"
            $table->string('pic', 120)->nullable();       // penanggung jawab (nama bebas)
            $table->string('location', 120)->nullable();  // mis. "CP2", "Semua WS"
            $table->string('phase', 16)->nullable();      // h7 | h3 | h1 | race | post
            $table->text('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->dateTime('done_at')->nullable();
            $table->foreignId('done_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['category', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gtr_prep_items');
    }
};
