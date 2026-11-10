<?php

namespace App\Http\Controllers\Api\Timing;

use App\Casts\DateTimeMs;
use App\Http\Controllers\Controller;
use App\Models\GtrRegistration;
use App\Services\GtrRacepackCheckin;
use App\Support\Bib;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Pengambilan racepack (role racepack / admin). Alur scanner: lookup → tampilkan detail → checkin. */
class RacepackController extends Controller
{
    /**
     * GET /api/timing/racepack/lookup?code=… — detail peserta + status, tanpa mengubah data.
     * 200 status=available|already · 404 not_found · 422 not_paid.
     */
    public function lookup(Request $request, GtrRacepackCheckin $racepack): JsonResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:40']]);

        return $this->respond($racepack->lookup($data['code']));
    }

    /**
     * POST /api/timing/racepack/checkin
     * Body: { code: nomor BIB | nomor registrasi, picked_by?: nama wakil, collected_at_ms? }
     * 200 status=ok|already · 404 not_found · 422 not_paid. Idempoten.
     */
    public function checkin(Request $request, GtrRacepackCheckin $racepack): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required'],
            'picked_by' => ['nullable', 'string', 'max:255'],
            'collected_at_ms' => ['nullable', 'integer', 'min:1577836800000', 'max:'.(DateTimeMs::toMs(now()) + 60_000)],
        ]);

        $at = ! empty($data['collected_at_ms']) ? DateTimeMs::toCarbon((int) $data['collected_at_ms']) : null;

        return $this->respond($racepack->checkin($data['code'], $request->user(), $data['picked_by'] ?? null, $at));
    }

    /** @param array{status: string, registration: ?GtrRegistration} $result */
    private function respond(array $result): JsonResponse
    {
        $reg = $result['registration'];

        $http = match ($result['status']) {
            GtrRacepackCheckin::NOT_FOUND => 404,
            GtrRacepackCheckin::NOT_PAID => 422,
            default => 200,
        };

        return response()->json([
            'status' => $result['status'],
            'participant' => $reg ? self::participant($reg) : null,
        ], $http);
    }

    public static function participant(GtrRegistration $reg): array
    {
        return [
            'bib' => Bib::normalize($reg->bib_number) ?? $reg->bib_number,
            'registration_number' => $reg->nomor_registrasi,
            'name' => $reg->displayName(),
            'category' => $reg->category?->name,
            'category_id' => $reg->gtr_category_id,
            'gender' => $reg->genderCode(),
            'size' => $reg->size,
            'nik' => $reg->nik, // verifikasi identitas (hanya role racepack / admin)
            'club' => $reg->club,
            'payment_status' => $reg->payment_status,
            'racepack_collected_at_ms' => DateTimeMs::toMs($reg->racepack_collected_at),
            'racepack_collected_by' => $reg->racepackCollector?->name,
            'racepack_picked_by' => $reg->racepack_picked_by,
        ];
    }
}
