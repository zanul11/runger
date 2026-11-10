<?php

use App\Services\GtrTimingSync;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kunjungan per kategori di satu pos, urut KM. Pos yang dilewati lebih dari sekali oleh satu
     * kategori (mis. 15K di Pertigaan Pos 1 Keteri: WS1 km 7.59 & WS2 km 12.3) punya >1 kunjungan:
     * [{"code":"WS1","km":7.59,"cutoff":null},{"code":"WS2","km":12.3,"cutoff":null}].
     */
    public function up(): void
    {
        Schema::table('gtr_category_timing_point', function (Blueprint $table) {
            $table->json('visits')->nullable()->after('cutoff_at');
        });

        GtrTimingSync::run();
    }

    public function down(): void
    {
        Schema::table('gtr_category_timing_point', function (Blueprint $table) {
            $table->dropColumn('visits');
        });
    }
};
