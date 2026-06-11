<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gtr_registrations', function (Blueprint $table) {
            $table->string('midtrans_order_id')->nullable()->unique()->after('pay');
            $table->string('snap_token')->nullable()->after('midtrans_order_id');
            $table->unsignedInteger('amount')->nullable()->after('snap_token');
            $table->timestamp('paid_at')->nullable()->after('payment_status');
        });
    }

    public function down(): void
    {
        Schema::table('gtr_registrations', function (Blueprint $table) {
            $table->dropColumn(['midtrans_order_id', 'snap_token', 'amount', 'paid_at']);
        });
    }
};
