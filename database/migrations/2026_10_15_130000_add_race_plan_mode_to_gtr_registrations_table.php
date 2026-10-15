<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gtr_registrations', function (Blueprint $table) {
            // Tampilan race plan yang dipilih peserta: 'pos' (per pos) atau 'km' (split per KM)
            $table->string('race_plan_mode', 8)->nullable()->after('target_finish_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('gtr_registrations', function (Blueprint $table) {
            $table->dropColumn('race_plan_mode');
        });
    }
};
