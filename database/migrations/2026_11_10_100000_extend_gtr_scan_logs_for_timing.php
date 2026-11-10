<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * gtr_scan_logs jadi log scan append-only untuk modul timing:
     *  - waktu presisi milidetik (DATETIME(3)),
     *  - BIB mentah yang diketik/discan (audit) + nomor urut pull-tag,
     *  - scan tidak dihapus: ditandai is_ignored + alasan (duplikat, dibatalkan, BIB tak terdaftar, ...),
     *  - scan BIB tak terdaftar tetap disimpan untuk audit → gtr_registration_id boleh null,
     *  - source: scan → qr, tambah pulltag.
     */
    public function up(): void
    {
        Schema::table('gtr_scan_logs', function (Blueprint $table) {
            $table->string('bib_input', 32)->nullable()->after('gtr_timing_point_id');
            $table->unsignedInteger('pulltag_seq')->nullable()->after('source');
            $table->dateTime('server_received_at', 3)->nullable()->after('clock_offset_ms');
            $table->boolean('is_ignored')->default(false)->after('is_flagged');
            $table->string('ignore_reason', 32)->nullable()->after('is_ignored');
            $table->foreignId('ignored_by')->nullable()->after('ignore_reason')->constrained('users')->nullOnDelete();
            $table->dateTime('ignored_at')->nullable()->after('ignored_by');

            $table->index(['gtr_timing_point_id', 'is_ignored']);
        });

        Schema::table('gtr_scan_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('gtr_registration_id')->nullable()->change();
            $table->dateTime('scanned_at', 3)->change();
            $table->dateTime('raw_device_time', 3)->nullable()->change();
            $table->string('source', 16)->default('qr')->change();
        });

        DB::table('gtr_scan_logs')->where('source', 'scan')->update(['source' => 'qr']);
    }

    public function down(): void
    {
        // Baris audit tanpa peserta (BIB tak terdaftar) tidak bisa dipertahankan bila kolom NOT NULL.
        DB::table('gtr_scan_logs')->whereNull('gtr_registration_id')->delete();
        DB::table('gtr_scan_logs')->where('source', 'qr')->update(['source' => 'scan']);
        DB::table('gtr_scan_logs')->where('source', 'pulltag')->update(['source' => 'manual']);

        Schema::table('gtr_scan_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('gtr_registration_id')->nullable(false)->change();
            $table->dateTime('scanned_at')->change();
            $table->dateTime('raw_device_time')->nullable()->change();
            $table->enum('source', ['scan', 'manual', 'video'])->default('scan')->change();
        });

        Schema::table('gtr_scan_logs', function (Blueprint $table) {
            $table->dropIndex(['gtr_timing_point_id', 'is_ignored']);
            $table->dropForeign(['ignored_by']);
            $table->dropColumn(['bib_input', 'pulltag_seq', 'server_received_at', 'is_ignored', 'ignore_reason', 'ignored_by', 'ignored_at']);
        });
    }
};
