<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // qr_token tidak dipakai lagi — identitas QR memakai nomor_registrasi.
        Schema::table('gtr_registrations', function (Blueprint $table) {
            $table->dropUnique(['qr_token']);
            $table->dropColumn('qr_token');
        });
    }

    public function down(): void
    {
        Schema::table('gtr_registrations', function (Blueprint $table) {
            $table->string('qr_token')->nullable()->unique()->after('bib_number');
        });
    }
};
