<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gtr_registrations', function (Blueprint $table) {
            if (Schema::hasColumn('gtr_registrations', 'status')) {
                $table->dropColumn('status');
            }
        });

        Schema::table('gtr_registrations', function (Blueprint $table) {
            $table->string('nomor_registrasi')->nullable()->unique()->after('gtr_category_id');
            $table->string('size')->nullable()->after('nomor_registrasi'); // XS..5XL
            $table->string('full_name')->nullable()->after('size');
            $table->string('nik', 16)->nullable()->after('full_name');
            $table->string('email')->nullable()->after('nik');
            $table->string('whatsapp', 30)->nullable()->after('email');
            $table->date('birth_date')->nullable()->after('whatsapp');
            $table->string('gender')->nullable()->after('birth_date'); // Laki-laki / Perempuan
            $table->string('address')->nullable()->after('gender');
            $table->string('club')->nullable()->after('address');
            $table->string('blood_type')->nullable()->after('club'); // A,B,AB,O
            $table->string('emergency_name')->nullable()->after('blood_type');
            $table->string('emergency_contact', 30)->nullable()->after('emergency_name');
            $table->string('pay', 50)->default('Transfer Bank')->after('emergency_contact');
            $table->string('payment_status')->default('pending')->after('pay'); // pending,paid,cancelled
            $table->boolean('agree_terms')->default(false)->after('payment_status');
        });
    }

    public function down(): void
    {
        Schema::table('gtr_registrations', function (Blueprint $table) {
            $table->dropColumn([
                'nomor_registrasi', 'size', 'full_name', 'nik', 'email', 'whatsapp', 'birth_date',
                'gender', 'address', 'club', 'blood_type', 'emergency_name', 'emergency_contact',
                'pay', 'payment_status', 'agree_terms',
            ]);
            $table->string('status')->default('pending');
        });
    }
};
