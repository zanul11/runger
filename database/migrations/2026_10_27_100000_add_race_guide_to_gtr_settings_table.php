<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gtr_settings', function (Blueprint $table) {
            // Konten slide Race Guide: syarat pengambilan race pack (RPC)
            $table->text('rpc_requirements')->nullable();   // satu baris = satu syarat
            $table->text('rpc_proxy_note')->nullable();     // opsi bila tak bisa hadir (diwakilkan / dikirim)
            $table->string('rpc_contact')->nullable();      // kontak info lebih lanjut
            // Race village / venue
            $table->string('venue_name')->nullable();
            $table->string('venue_address')->nullable();
            $table->string('venue_maps_url')->nullable();
            $table->text('venue_directions')->nullable();   // satu baris = satu langkah
            $table->text('venue_facilities')->nullable();   // satu baris = satu fasilitas
            // Parkir
            $table->string('parking_name')->nullable();
            $table->text('parking_notes')->nullable();      // satu baris = satu poin
            // Race guide final yang dipublikasikan ke website (gambar hasil render)
            $table->json('race_guide_slides')->nullable();
            $table->string('race_guide_zip')->nullable();
            $table->dateTime('race_guide_published_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('gtr_settings', function (Blueprint $table) {
            $table->dropColumn([
                'rpc_requirements', 'rpc_proxy_note', 'rpc_contact',
                'venue_name', 'venue_address', 'venue_maps_url', 'venue_directions', 'venue_facilities',
                'parking_name', 'parking_notes',
                'race_guide_slides', 'race_guide_zip', 'race_guide_published_at',
            ]);
        });
    }
};
