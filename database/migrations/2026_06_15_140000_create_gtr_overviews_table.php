<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gtr_overviews', function (Blueprint $table) {
            $table->id();
            $table->string('eyebrow')->nullable();
            $table->string('heading')->nullable();
            $table->text('paragraph_1')->nullable();
            $table->text('paragraph_2')->nullable();
            $table->string('photo_main')->nullable();
            $table->string('photo_2')->nullable();
            $table->string('photo_3')->nullable();
            $table->timestamps();
        });

        // Seed teks default yang sebelumnya hardcoded (foto pakai fallback aset bawaan).
        DB::table('gtr_overviews')->insert([
            'eyebrow' => 'Event Overview',
            'heading' => 'Gerung Trail Run 2026',
            'paragraph_1' => 'Edisi perdana race trail yang digelar Runners Gerung di Bukit Keteri, Gerung. Rute melintasi punggung bukit, hutan rendah, dan jalur pedesaan dengan panorama Lombok Barat saat matahari terbit.',
            'paragraph_2' => 'Dua kategori dirancang untuk semua level — dari pelari yang ingin mencoba trail pertamanya, sampai yang mencari tantangan elevasi & jarak.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('gtr_overviews');
    }
};
