<?php

use App\Support\GtrDocumentDefaults;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Dokumen peserta GTR (Waiver, PAR-Q, Surat Kuasa) yang bisa diedit di admin lalu
        // dipublikasikan sebagai PDF untuk diunduh dari menu Race Info.
        Schema::create('gtr_documents', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('nav_label');                 // label di menu Race Info
            $table->string('title_id');
            $table->string('title_en')->nullable();
            $table->text('meta_id')->nullable();         // chip info di header (1 baris = 1 chip)
            $table->text('meta_en')->nullable();
            $table->string('footer_id')->nullable();
            $table->string('footer_en')->nullable();
            $table->unsignedSmallInteger('font_size')->default(100); // persen
            $table->boolean('has_en')->default(true);
            $table->json('content_id')->nullable();      // blok Filament Builder
            $table->json('content_en')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(false);
            $table->string('pdf_id')->nullable();
            $table->string('pdf_en')->nullable();
            $table->dateTime('published_at')->nullable();
            $table->timestamps();
        });

        $now = now();
        foreach (GtrDocumentDefaults::all() as $doc) {
            DB::table('gtr_documents')->insert(array_merge($doc, [
                'content_id' => json_encode($doc['content_id']),
                'content_en' => json_encode($doc['content_en']),
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('gtr_documents');
    }
};
