<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Data awal rute 15K GTR 2026 (dari peta 15K panitia). */
    private const SIGNS_15K = [
        'red' => [2, 2.15, 2.6, 3.4, 4.4, 5, 7.5, 8.5, 11],
        'blue' => [0.3, 1.5, 13.5, 13.8],
        'green' => [12.7],
    ];

    private const MARSHALS_15K = [
        [0.3, 'Basecamp'], [2, 'Runger'], [2.15, 'Basecamp'], [3.4, 'Runger'],
        [6.3, 'Runger'], [7.5, 'Basecamp'], [8.5, 'Basecamp'], [10, 'Basecamp'],
    ];

    public function up(): void
    {
        // Titik operasional KHUSUS PANITIA (tidak tampil di Course Map peserta): sign arah & marshal.
        Schema::table('gtr_categories', function (Blueprint $table) {
            $table->json('crew_signs')->nullable()->after('course_pois');
            $table->json('crew_marshals')->nullable()->after('crew_signs');
        });

        $cat = DB::table('gtr_categories')
            ->where(fn ($q) => $q->where('name', '15K')->orWhere('distance', 'like', '15%'))
            ->orderBy('id')->first();
        if (! $cat) {
            return;
        }

        $signs = collect(self::SIGNS_15K)
            ->flatMap(fn ($kms, $color) => array_map(fn ($km) => ['km' => $km, 'color' => $color, 'note' => null], $kms))
            ->sortBy('km')->values()->all();
        $marshals = array_map(fn ($m) => ['km' => $m[0], 'team' => $m[1], 'count' => 1, 'note' => null], self::MARSHALS_15K);

        DB::table('gtr_categories')->where('id', $cat->id)->update([
            'crew_signs' => json_encode($signs),
            'crew_marshals' => json_encode($marshals),
        ]);
    }

    public function down(): void
    {
        Schema::table('gtr_categories', function (Blueprint $table) {
            $table->dropColumn(['crew_signs', 'crew_marshals']);
        });
    }
};
