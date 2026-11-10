<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gtr_timing_point_assignments', function (Blueprint $table) {
            // Device scanner terakhir yang login dengan penugasan ini.
            $table->string('device_id', 64)->nullable()->after('gtr_timing_point_id');
            $table->dateTime('device_seen_at')->nullable()->after('device_id');
        });
    }

    public function down(): void
    {
        Schema::table('gtr_timing_point_assignments', function (Blueprint $table) {
            $table->dropColumn(['device_id', 'device_seen_at']);
        });
    }
};
