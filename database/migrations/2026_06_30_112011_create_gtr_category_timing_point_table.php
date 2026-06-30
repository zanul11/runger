<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pivot: titik timing mana yang harus dilewati tiap kategori, urutannya,
        // wajib/tidak, dan cutoff per titik.
        Schema::create('gtr_category_timing_point', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gtr_category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('gtr_timing_point_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sequence')->default(0);   // urutan lintasan
            $table->boolean('is_mandatory')->default(true);
            $table->dateTime('cutoff_at')->nullable();
            $table->timestamps();

            $table->unique(['gtr_category_id', 'gtr_timing_point_id'], 'gtr_cat_tp_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gtr_category_timing_point');
    }
};
