<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Role panitia: panitia biasa di GTR Crew — hanya checklist Persiapan (tanpa scan & data peserta). */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'marshal', 'racepack', 'panitia'])->default('admin')->change();
        });
    }

    public function down(): void
    {
        // Akun panitia tidak punya padanan di skema lama → dihapus (beserta token login).
        $ids = DB::table('users')->where('role', 'panitia')->pluck('id');
        DB::table('personal_access_tokens')->where('tokenable_type', \App\Models\User::class)->whereIn('tokenable_id', $ids)->delete();
        DB::table('users')->whereIn('id', $ids)->delete();

        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'marshal', 'racepack'])->default('admin')->change();
        });
    }
};
