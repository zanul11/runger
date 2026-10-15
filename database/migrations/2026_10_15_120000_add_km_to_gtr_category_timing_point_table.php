<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gtr_category_timing_point', function (Blueprint $table) {
            // Posisi titik timing di rute kategori (untuk live tracking di peta)
            $table->decimal('km', 6, 2)->nullable()->after('sequence');
        });
    }

    public function down(): void
    {
        Schema::table('gtr_category_timing_point', function (Blueprint $table) {
            $table->dropColumn('km');
        });
    }
};
