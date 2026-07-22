<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gtr_registrations', function (Blueprint $table) {
            // Jejak audit saat peserta pindah kategori (BIB lama sebelum diganti).
            $table->string('previous_bib_number')->nullable()->after('bib_number');
        });
    }

    public function down(): void
    {
        Schema::table('gtr_registrations', function (Blueprint $table) {
            $table->dropColumn('previous_bib_number');
        });
    }
};
