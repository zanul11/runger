<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gtr_categories', function (Blueprint $table) {
            // Perlengkapan opsional / disarankan (pelengkap mandatory_gear)
            $table->json('recommended_gear')->nullable()->after('mandatory_gear');
        });
    }

    public function down(): void
    {
        Schema::table('gtr_categories', function (Blueprint $table) {
            $table->dropColumn('recommended_gear');
        });
    }
};
