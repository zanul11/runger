<?php

namespace App\Http\Controllers\Api\Timing;

use App\Casts\DateTimeMs;
use App\Http\Controllers\Controller;
use App\Jobs\RecomputeGtrResults;
use App\Services\GtrScanIngestor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScanBatchController extends Controller
{
    public const MAX_BATCH = 500;

    /**
     * POST /api/timing/scans/batch
     *
     * Body: { scans: [{ uuid, bib, timing_point_id, device_time_ms, clock_offset_ms,
     *                   source: qr|manual|pulltag, pulltag_seq?, cancelled? }] }
     * Balikan: { accepted: [uuid], rejected: [{uuid, reason}], server_time_ms }
     *
     * Validasi dilakukan PER ITEM (bukan seluruh request) supaya satu item rusak tidak
     * menyumbat antrean device. Semua jawaban final; hanya error jaringan/5xx yang perlu diulang.
     */
    public function __invoke(Request $request, GtrScanIngestor $ingestor): JsonResponse
    {
        $request->validate([
            'scans' => ['required', 'array', 'max:'.self::MAX_BATCH],
        ]);

        $result = $ingestor->ingest(
            $request->user(),
            SessionController::deviceId($request),
            array_values($request->input('scans')),
        );

        if ($ingestor->touchedCategoryIds) {
            RecomputeGtrResults::dispatch($ingestor->touchedCategoryIds)->afterResponse();
        }

        return response()->json($result + ['server_time_ms' => DateTimeMs::toMs(now())]);
    }
}
