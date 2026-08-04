<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gtr_discounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');                         // Nama diskon, mis. "Early Squad"
            $table->string('code')->unique();               // Kode diskon, mis. "RUNGER25"
            $table->string('type')->default('fixed');       // fixed (IDR) | percent (%)
            $table->unsignedInteger('value')->default(0);   // nominal IDR atau persen
            $table->unsignedInteger('quota')->nullable();   // jumlah maksimal pemakaian (null = tak terbatas)
            $table->unsignedInteger('used_count')->default(0); // sudah dipakai berapa kali
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gtr_discounts');
    }
};
