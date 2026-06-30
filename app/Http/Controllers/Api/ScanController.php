<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreScansRequest;
use App\Models\GtrRegistration;
use App\Models\GtrScanLog;
use Illuminate\Http\JsonResponse;

class ScanController extends Controller
{
    /**
     * Terima BATCH scan dari scanner PWA.
     *
     * Dua jaminan penting:
     *  1) IDEMPOTEN — setiap item dikunci oleh client_uuid (unique). updateOrCreate
     *     berdasarkan client_uuid membuat pengiriman ulang aman (tidak dobel).
     *  2) POS TERKUNCI — timing_point_id tiap item WAJIB sama dengan pos aktif
     *     marshal ini. Item dengan pos lain ditolak. Ini menjamin waktu tercatat
     *     sesuai LOKASI penugasan, bukan pos sembarang.
     *
     * Balikan: { accepted: [client_uuid...], rejected: [{client_uuid, reason}] }
     */
    public function store(StoreScansRequest $request): JsonResponse
    {
        $user = $request->user();
        $assignment = $user->activeAssignment();

        if (! $assignment) {
            return response()->json([
                'message' => 'Belum ditugaskan ke pos.',
            ], 403);
        }

        $postTimingPointId = $assignment->gtr_timing_point_id;

        // Pre-resolve qr_token -> registration_id sekali jalan (hindari N+1).
        $tokens = collect($request->input('scans'))->pluck('qr_token')->unique()->all();
        $regByToken = GtrRegistration::whereIn('qr_token', $tokens)
            ->pluck('id', 'qr_token');

        $accepted = [];
        $rejected = [];

        foreach ($request->input('scans') as $item) {
            $uuid = $item['client_uuid'];

            // (1) Validasi pos: harus cocok dengan pos aktif marshal.
            if ((int) $item['timing_point_id'] !== (int) $postTimingPointId) {
                $rejected[] = ['client_uuid' => $uuid, 'reason' => 'wrong_post'];

                continue;
            }

            // (2) qr_token harus dikenal (FK butuh registration).
            $registrationId = $regByToken[$item['qr_token']] ?? null;
            if (! $registrationId) {
                $rejected[] = ['client_uuid' => $uuid, 'reason' => 'unknown_qr'];

                continue;
            }

            // (3) updateOrCreate berdasarkan client_uuid -> IDEMPOTEN.
            GtrScanLog::updateOrCreate(
                ['client_uuid' => $uuid],
                [
                    'gtr_registration_id' => $registrationId,
                    'gtr_timing_point_id' => $postTimingPointId,
                    'scanned_at' => $item['scanned_at'],
                    'raw_device_time' => $item['raw_device_time'] ?? null,
                    'clock_offset_ms' => $item['clock_offset_ms'] ?? 0,
                    'source' => $item['source'] ?? GtrScanLog::SOURCE_SCAN,
                    'device_id' => $item['device_id'] ?? $user->currentAccessToken()?->name,
                    'is_flagged' => false,
                ],
            );

            $accepted[] = $uuid;
        }

        return response()->json([
            'accepted' => $accepted,
            'rejected' => $rejected,
        ]);
    }
}
