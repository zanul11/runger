<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gtr_rules', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Seed isi rules yang sebelumnya hardcoded agar tidak hilang.
        $now = now();
        $rules = [
            ['Usia Minimum', '7K: 14 tahun · 15K: 17 tahun, dengan persetujuan orang tua / wali untuk kategori 7K.'],
            ['Mandatory Gear', 'Hidrasi minimal 1L (7K), 1.5L (15K), peluit, headlamp untuk start sebelum sunrise, sepatu trail wajib.'],
            ['BIB Collection', 'BIB diambil sehari sebelum race (28 November) di race center sekitar Bukit Keteri. Wajib bawa ID & bukti registrasi. Tidak ada race day BIB.'],
            ['Cut-Off Time', '7K: 2 jam · 15K: 4 jam dari flag-off masing-masing kategori. Peserta lewat COT diarahkan sweeper.'],
            ['Water Station', 'WS tersedia setiap 3–5 km untuk semua kategori. Isi botol pribadi — single use plastic dilarang di seluruh rute.'],
            ['Litter & Environment', 'Buang sampah pada drop point WS. Peserta tertangkap littering akan DQ otomatis. Leave No Trace policy.'],
            ['Safety & Medic', 'Tim medis siaga di tiap WS dan finish line. Peserta diperbolehkan retire kapan saja — hubungi marshal terdekat.'],
            ['Transfer & Refund', 'Transfer BIB diperbolehkan sampai H-14. Refund hanya untuk pembatalan event resmi oleh panitia.'],
        ];

        foreach ($rules as $i => [$title, $content]) {
            DB::table('gtr_rules')->insert([
                'title' => $title,
                'content' => $content,
                'sort_order' => $i + 1,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('gtr_rules');
    }
};
