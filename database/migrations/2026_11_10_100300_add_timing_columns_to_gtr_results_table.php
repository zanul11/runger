<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Hasil dalam milidetik + detail per pos. Kolom *_seconds lama tetap ada (diisi dari ms)
     * karena dipakai halaman hasil & sertifikat.
     */
    public function up(): void
    {
        Schema::table('gtr_results', function (Blueprint $table) {
            $table->foreignId('gtr_category_id')->nullable()->after('gtr_registration_id')->constrained()->nullOnDelete();
            $table->dateTime('start_time', 3)->nullable()->after('gtr_category_id');
            $table->dateTime('finish_time', 3)->nullable()->after('start_time');
            $table->unsignedBigInteger('gun_time_ms')->nullable()->after('finish_time');
            $table->unsignedBigInteger('net_time_ms')->nullable()->after('gun_time_ms');
            $table->json('checkpoints_json')->nullable()->after('status');
            // DQ otomatis hanya flag: admin mereview sebelum final.
            $table->boolean('needs_review')->default(false)->after('checkpoints_json');
            $table->string('review_note')->nullable()->after('needs_review');
        });
    }

    public function down(): void
    {
        Schema::table('gtr_results', function (Blueprint $table) {
            $table->dropForeign(['gtr_category_id']);
            $table->dropColumn(['gtr_category_id', 'start_time', 'finish_time', 'gun_time_ms', 'net_time_ms', 'checkpoints_json', 'needs_review', 'review_note']);
        });
    }
};
