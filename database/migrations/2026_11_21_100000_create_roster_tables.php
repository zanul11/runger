<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Roster panitia hari H (GTR Crew → Roster). Struktur kepanitiaan per event dikelola admin:
 * area → grup → pos/divisi (min–max petugas), lalu petugas diisi per slot saat rapat.
 *
 * Info event roster memakai tabel `events` yang sudah ada: nama = title, tanggal = date,
 * kategori = distance_text; kolom tambahan khusus roster ditambahkan di bawah.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('lokasi_race_village', 160)->nullable()->after('location');
            $table->string('lokasi_rute', 160)->nullable()->after('lokasi_race_village');
            $table->string('catatan_open_gate', 160)->nullable()->after('lokasi_rute');
            $table->string('penyelenggara', 160)->nullable()->after('catatan_open_gate');
            // Event yang tampil di /crew → Roster. Maksimal satu (dijaga di model Event).
            $table->boolean('roster_aktif')->default(false)->after('is_published');
        });

        Schema::create('roster_areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('nama', 80);
            $table->string('slug', 80);
            $table->unsignedInteger('urutan')->default(0);
            $table->timestamps();

            $table->unique(['event_id', 'slug']);
        });

        Schema::create('roster_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('area_id')->constrained('roster_areas')->cascadeOnDelete();
            $table->string('nama', 80);
            $table->unsignedInteger('urutan')->default(0);
            $table->timestamps();
        });

        Schema::create('roster_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('roster_groups')->cascadeOnDelete();
            $table->string('nama', 120);
            $table->text('deskripsi')->nullable();
            $table->unsignedSmallInteger('min_petugas')->default(1);
            $table->unsignedSmallInteger('max_petugas')->default(1);
            $table->json('km')->nullable();
            $table->string('ikon', 16)->default('flag');
            $table->string('catatan_tambahan', 160)->nullable();
            $table->unsignedInteger('urutan')->default(0);
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        Schema::create('roster_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('position_id')->constrained('roster_positions')->cascadeOnDelete();
            $table->unsignedSmallInteger('slot_no');
            $table->string('nama', 120)->nullable();
            $table->string('no_hp', 32)->nullable();
            $table->string('catatan', 255)->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['position_id', 'slot_no']);
        });

        Schema::create('roster_consumption_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('tamu_tambahan')->default(10);
            $table->unsignedSmallInteger('air_per_orang')->default(2);
            $table->json('jadwal')->nullable();
            $table->string('catatan', 255)->nullable();
            // Area acuan "paket sarapan ke pos lapangan" (null = area berslug "lapangan").
            $table->foreignId('area_acuan_id')->nullable()->constrained('roster_areas')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roster_consumption_settings');
        Schema::dropIfExists('roster_assignments');
        Schema::dropIfExists('roster_positions');
        Schema::dropIfExists('roster_groups');
        Schema::dropIfExists('roster_areas');

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['lokasi_race_village', 'lokasi_rute', 'catatan_open_gate', 'penyelenggara', 'roster_aktif']);
        });
    }
};
