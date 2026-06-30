<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // APPEND-ONLY: tiap pemindaian QR satu baris. Tidak pernah di-update kecuali
        // untuk idempotensi via client_uuid. Sumber kebenaran waktu lintas pos.
        Schema::create('gtr_scan_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gtr_registration_id')->constrained()->cascadeOnDelete();
            $table->foreignId('gtr_timing_point_id')->constrained()->cascadeOnDelete();
            $table->dateTime('scanned_at');                       // raw_device_time + clock_offset_ms
            $table->string('device_id')->nullable();
            $table->enum('source', ['scan', 'manual', 'video'])->default('scan');
            $table->uuid('client_uuid')->unique();                // idempotensi dari device
            $table->dateTime('raw_device_time')->nullable();
            $table->integer('clock_offset_ms')->default(0);
            $table->boolean('is_flagged')->default(false);        // mis. qr_token tak dikenal saat scan
            $table->timestamps();

            $table->index(['gtr_timing_point_id', 'scanned_at']);
            $table->index(['gtr_registration_id', 'gtr_timing_point_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gtr_scan_logs');
    }
};
