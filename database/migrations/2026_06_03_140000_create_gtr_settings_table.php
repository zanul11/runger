<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gtr_settings', function (Blueprint $table) {
            $table->id();
            $table->string('header_image')->nullable();
            $table->string('eyebrow')->nullable();
            $table->string('title')->nullable();
            $table->string('date_text')->nullable();
            $table->string('location_text')->nullable();
            $table->string('categories_text')->nullable();
            $table->timestamps();
        });

        DB::table('gtr_settings')->insert([
            'eyebrow' => '1st Edition · Bukit Keteri Trail',
            'title' => 'Gerung Trail Run 2026',
            'date_text' => 'Minggu, 29 November 2026',
            'location_text' => 'Bukit Keteri, Gerung — Lombok Barat',
            'categories_text' => 'Kategori 7K & 15K',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('gtr_settings');
    }
};
