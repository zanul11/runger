<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Audit koreksi manual hasil. Koreksi aktif diterapkan ulang oleh result engine
     * setiap hitung ulang, jadi tidak hilang saat scan baru masuk.
     */
    public function up(): void
    {
        Schema::create('gtr_result_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gtr_registration_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('field', 32);              // status | finish_time | start_time
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->text('reason');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['gtr_registration_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gtr_result_adjustments');
    }
};
