<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Role bendahara (GTR Crew → Pengeluaran) + tabel pengeluaran panitia beserta kwitansinya.
     * File kwitansi di disk "local" (privat), hanya bisa dibuka lewat API dengan token.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'marshal', 'racepack', 'panitia', 'bendahara'])->default('admin')->change();
        });

        Schema::create('gtr_expenses', function (Blueprint $table) {
            $table->id();
            $table->date('spent_on');
            $table->unsignedBigInteger('amount');
            $table->string('pic', 120);
            $table->text('description');
            $table->string('receipt_path')->nullable();
            $table->string('receipt_mime', 100)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index('spent_on');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gtr_expenses');

        // Akun bendahara tidak punya padanan di skema lama → dihapus (beserta token login).
        $ids = DB::table('users')->where('role', 'bendahara')->pluck('id');
        DB::table('personal_access_tokens')->where('tokenable_type', \App\Models\User::class)->whereIn('tokenable_id', $ids)->delete();
        DB::table('users')->whereIn('id', $ids)->delete();

        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'marshal', 'racepack', 'panitia'])->default('admin')->change();
        });
    }
};
