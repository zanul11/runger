<?php

use App\Services\GtrTimingSync;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Titik timing kini dibentuk otomatis dari "Titik di Rute" kategori (lihat GtrTimingSync).
        Schema::table('gtr_timing_points', function (Blueprint $table) {
            $table->string('location_key')->nullable()->unique()->after('code'); // start / finish / loc:<slug lokasi>
        });

        // Petugas (marshal) yang melakukan scan.
        Schema::table('gtr_scan_logs', function (Blueprint $table) {
            $table->foreignId('scanned_by')->nullable()->after('gtr_timing_point_id')->constrained('users')->nullOnDelete();
        });

        GtrTimingSync::run();
    }

    public function down(): void
    {
        Schema::table('gtr_scan_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('scanned_by');
        });
        Schema::table('gtr_timing_points', function (Blueprint $table) {
            $table->dropUnique(['location_key']);
            $table->dropColumn('location_key');
        });
    }
};
