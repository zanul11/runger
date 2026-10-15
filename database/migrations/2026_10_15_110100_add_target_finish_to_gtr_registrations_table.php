<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gtr_registrations', function (Blueprint $table) {
            // Race plan peserta: target waktu finish (menit) untuk estimasi & pace band
            $table->unsignedSmallInteger('target_finish_minutes')->nullable()->after('race_status');
        });
    }

    public function down(): void
    {
        Schema::table('gtr_registrations', function (Blueprint $table) {
            $table->dropColumn('target_finish_minutes');
        });
    }
};
