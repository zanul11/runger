<?php

use App\Support\GtrDocumentHtml;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Isi dokumen peserta diedit dengan editor teks penuh (HTML), bukan blok lagi. */
    public function up(): void
    {
        Schema::table('gtr_documents', function (Blueprint $table) {
            $table->longText('body_id')->nullable()->after('has_en');
            $table->longText('body_en')->nullable()->after('body_id');
        });

        foreach (DB::table('gtr_documents')->get() as $row) {
            DB::table('gtr_documents')->where('id', $row->id)->update([
                'body_id' => GtrDocumentHtml::fromBlocks(json_decode((string) $row->content_id, true) ?: [], 'id'),
                'body_en' => GtrDocumentHtml::fromBlocks(json_decode((string) $row->content_en, true) ?: [], 'en'),
            ]);
        }

        Schema::table('gtr_documents', function (Blueprint $table) {
            $table->dropColumn(['content_id', 'content_en']);
        });
    }

    public function down(): void
    {
        Schema::table('gtr_documents', function (Blueprint $table) {
            $table->json('content_id')->nullable();
            $table->json('content_en')->nullable();
            $table->dropColumn(['body_id', 'body_en']);
        });
    }
};
