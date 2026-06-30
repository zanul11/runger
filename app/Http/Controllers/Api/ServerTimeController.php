<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ServerTimeController extends Controller
{
    /**
     * Waktu server untuk koreksi clock device (clock_offset_ms = server - device).
     */
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'server_time' => now()->toIso8601String(),
        ]);
    }
}
