<?php

namespace App\Http\Controllers\Api\Timing;

use App\Casts\DateTimeMs;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class TimeController extends Controller
{
    /**
     * GET /api/timing/time — jam server untuk resync jam device.
     * Scanner: offset = server_time_ms + (rtt / 2) - jam_device_saat_terima.
     */
    public function __invoke(): JsonResponse
    {
        $now = now();

        return response()->json([
            'server_time_ms' => DateTimeMs::toMs($now),
            'server_time' => $now->format('Y-m-d\TH:i:s.vP'),
            'timezone' => config('app.timezone'),
        ]);
    }
}
