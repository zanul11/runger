<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sistem ini single-event (GTR), jadi event_id pada timing point redundan.
        // Urutan penting: drop FK dulu (MySQL: index unik dipakai FK), baru unique,
        // lalu kolom. Satu closure agar SQLite merebuild tabel sekali jalan.
        Schema::table('gtr_timing_points', function (Blueprint $table) {
            $table->dropForeign(['event_id']);
            $table->dropUnique(['event_id', 'code']);
            $table->dropColumn('event_id');
            $table->unique('code');
        });
    }

    public function down(): void
    {
        Schema::table('gtr_timing_points', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->foreignId('event_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->unique(['event_id', 'code']);
        });
    }
};
