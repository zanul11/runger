<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gtr_categories', function (Blueprint $table) {
            // Prefix nomor BIB per kategori, mis. "7" -> BIB 7001, 7002, ...
            $table->string('bib_prefix', 8)->nullable()->after('slug');
        });
    }

    public function down(): void
    {
        Schema::table('gtr_categories', function (Blueprint $table) {
            $table->dropColumn('bib_prefix');
        });
    }
};
