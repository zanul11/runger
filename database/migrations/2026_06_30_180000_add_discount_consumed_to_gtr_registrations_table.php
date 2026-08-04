<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gtr_registrations', function (Blueprint $table) {
            // Menandai apakah registrasi ini SEDANG memakai slot kuota voucher.
            $table->boolean('discount_consumed')->default(false)->after('discount_amount');
        });

        // Data lama: yang punya voucher & belum cancelled dianggap sedang memakai slot.
        DB::table('gtr_registrations')
            ->whereNotNull('gtr_discount_id')
            ->where('payment_status', '!=', 'cancelled')
            ->update(['discount_consumed' => true]);
    }

    public function down(): void
    {
        Schema::table('gtr_registrations', function (Blueprint $table) {
            $table->dropColumn('discount_consumed');
        });
    }
};
