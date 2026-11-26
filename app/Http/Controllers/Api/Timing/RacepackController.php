<?php

namespace App\Http\Controllers\Api\Timing;

use App\Casts\DateTimeMs;
use App\Http\Controllers\Controller;
use App\Models\GtrRacepackLog;
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

        // Antrean offline sisa uji coba (sebelum gtr:reset-race-day) → tolak, jangan tandai diambil.
        $resetAtMs = \App\Models\GtrSetting::timingResetAtMs();
        if ($at && $resetAtMs !== null && DateTimeMs::toMs($at) < $resetAtMs) {
            return response()->json(['status' => 'before_reset', 'participant' => null], 409);
        }

        return $this->respond($racepack->checkin($data['code'], $request->user(), $data['picked_by'] ?? null, $at));
    }

    /**
     * GET /api/timing/racepack/history — riwayat penyerahan race pack, terbaru dulu:
     * waktu, petugas, nama wakil bila diwakilkan, dan pembatalan (siapa & kapan).
     */
    public function history(): JsonResponse
    {
        // ponytail: semua baris sekaligus (ratusan peserta); tambah paginasi bila > ~2000.
        $rows = GtrRacepackLog::with(['registration.category', 'collector', 'canceller'])
            ->latest('collected_at')->latest('id')->get()
            ->map(fn (GtrRacepackLog $l) => [
                'id' => $l->id,
                'bib' => Bib::normalize($l->registration->bib_number) ?? $l->registration->bib_number,
                'registration_number' => $l->registration->nomor_registrasi,
                'name' => $l->registration->displayName(),
                'category' => $l->registration->category?->distance ?: $l->registration->category?->name,
                'size' => $l->registration->size,
                'collected_at_ms' => DateTimeMs::toMs($l->collected_at),
                'collected_by' => $l->collector?->name,
                'picked_by' => $l->picked_by,
                'cancelled_at_ms' => DateTimeMs::toMs($l->cancelled_at),
                'cancelled_by' => $l->canceller?->name,
            ]);

        $paid = GtrRegistration::where('payment_status', 'paid');

        return response()->json([
            'total' => (clone $paid)->count(),
            'collected' => $paid->whereNotNull('racepack_collected_at')->count(),
            'items' => $rows,
        ]);
    }

    /**
     * POST /api/timing/racepack/cancel — batalkan pengambilan (admin saja). Body: { code }.
     * 200 status=cancelled · 409 status=not_collected · 404 not_found.
     */
    public function cancel(Request $request, GtrRacepackCheckin $racepack): JsonResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:40']]);
        $reg = $racepack->resolve($data['code']);
        if (! $reg) {
            return response()->json(['status' => 'not_found', 'participant' => null], 404);
        }

        $ok = $racepack->cancel($reg, $request->user());

        return response()->json([
            'status' => $ok ? 'cancelled' : 'not_collected',
            'participant' => self::participant($reg->fresh(['category', 'racepackCollector'])),
        ], $ok ? 200 : 409);
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
