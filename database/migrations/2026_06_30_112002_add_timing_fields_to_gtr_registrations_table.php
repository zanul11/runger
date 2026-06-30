<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gtr_registrations', function (Blueprint $table) {
            // Token QR acak yang dicetak di BIB & dipindai marshal.
            $table->string('qr_token')->nullable()->unique()->after('bib_number');
            // Status lomba (terpisah dari payment_status pendaftaran).
            // registered, dns (tidak start), dnf, dq, finisher
            $table->string('race_status')->default('registered')->after('qr_token');
        });
    }

    public function down(): void
    {
        Schema::table('gtr_registrations', function (Blueprint $table) {
            $table->dropColumn(['qr_token', 'race_status']);
        });
    }
};
