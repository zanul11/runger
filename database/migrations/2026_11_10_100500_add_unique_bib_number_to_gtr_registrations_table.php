<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Jangan sentuh data: bila ada BIB kembar, hentikan dan minta diperbaiki manual dulu.
        $dupes = DB::table('gtr_registrations')
            ->whereNotNull('bib_number')
            ->select('bib_number')
            ->groupBy('bib_number')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('bib_number');

        if ($dupes->isNotEmpty()) {
            throw new RuntimeException('BIB kembar, perbaiki dulu sebelum migrate: '.$dupes->implode(', '));
        }

        Schema::table('gtr_registrations', function (Blueprint $table) {
            $table->unique('bib_number');
        });
    }

    public function down(): void
    {
        Schema::table('gtr_registrations', function (Blueprint $table) {
            $table->dropUnique(['bib_number']);
        });
    }
};
