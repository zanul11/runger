<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gtr_payments', function (Blueprint $table) {
            // Catatan manual admin (mis. selisih upgrade kategori dibayar via transfer).
            $table->text('notes')->nullable()->after('raw');
        });
    }

    public function down(): void
    {
        Schema::table('gtr_payments', function (Blueprint $table) {
            $table->dropColumn('notes');
        });
    }
};
