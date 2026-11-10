<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Role baru: petugas racepack (hanya mode racepack di scanner, tanpa akses timing / admin). */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'marshal', 'racepack'])->default('admin')->change();
        });
    }

    public function down(): void
    {
        // Akun petugas racepack tidak dihapus; dikembalikan ke marshal agar enum lama valid.
        DB::table('users')->where('role', 'racepack')->update(['role' => 'marshal']);

        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'marshal'])->default('admin')->change();
        });
    }
};
