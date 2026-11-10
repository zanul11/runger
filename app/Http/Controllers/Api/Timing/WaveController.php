<?php

namespace App\Http\Controllers\Api\Timing;

use App\Casts\DateTimeMs;
use App\Http\Controllers\Controller;
use App\Jobs\RecomputeGtrResults;
use App\Models\GtrCategory;
use App\Services\GtrGunStart;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WaveController extends Controller
{
    /**
     * POST /api/timing/waves/{category}/start — tembak start kategori (admin).
     *
     * Body opsional: fired_at_ms (waktu terkoreksi dari device saat tombol ditekan; default
     * jam server), force (true = timpa gun time yang sudah tercatat).
     * 409 bila sudah ditembak dan tidak force.
     */
    public function start(Request $request, GtrCategory $category, GtrGunStart $gun): JsonResponse
    {
        $data = $request->validate([
            'fired_at_ms' => ['nullable', 'integer', 'min:1577836800000', 'max:'.(DateTimeMs::toMs(now()) + 60_000)],
            'force' => ['nullable', 'boolean'],
        ]);

        $at = ! empty($data['fired_at_ms']) ? DateTimeMs::toCarbon((int) $data['fired_at_ms']) : null;
        $result = $gun->fire($category, $request->user(), $at, (bool) ($data['force'] ?? false));
        $cat = $result['category'];

        if ($result['fired']) {
            RecomputeGtrResults::dispatch([$cat->id])->afterResponse();
        }

        return response()->json([
            'fired' => $result['fired'],
            'message' => $result['fired'] ? 'Start tercatat.' : 'Kategori ini sudah di-start.',
            'category' => [
                'id' => $cat->id,
                'name' => $cat->name,
                'gun_fired_at_ms' => DateTimeMs::toMs($cat->gun_fired_at),
                'gun_fired_at' => $cat->gun_fired_at?->format('Y-m-d H:i:s.v'),
                'gun_fired_by' => $cat->gun_fired_by,
            ],
        ], $result['fired'] ? 200 : 409);
    }
}
