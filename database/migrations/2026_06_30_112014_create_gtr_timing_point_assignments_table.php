<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Penugasan marshal (user) ke satu pos timing pada satu event.
        // Aturan: satu marshal hanya boleh 1 baris is_active=true per event
        // (ditegakkan di service layer; lihat MarshalService).
        Schema::create('gtr_timing_point_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('gtr_timing_point_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->dateTime('assigned_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'event_id', 'is_active']);
            $table->index(['gtr_timing_point_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gtr_timing_point_assignments');
    }
};
