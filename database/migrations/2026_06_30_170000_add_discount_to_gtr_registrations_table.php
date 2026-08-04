<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gtr_registrations', function (Blueprint $table) {
            $table->foreignId('gtr_discount_id')->nullable()->after('amount')->nullOnDelete();
            $table->string('discount_code')->nullable()->after('gtr_discount_id'); // salinan kode saat dipakai
            $table->unsignedInteger('discount_amount')->default(0)->after('discount_code'); // potongan (IDR)
        });
    }

    public function down(): void
    {
        Schema::table('gtr_registrations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('gtr_discount_id');
            $table->dropColumn(['discount_code', 'discount_amount']);
        });
    }
};
