<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GtrRegistration;
use App\Models\GtrScanLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class RosterController extends Controller
{
    /**
     * Roster peserta untuk diunduh & dipakai offline oleh scanner.
     *
     * Diambil dari POS aktif marshal (bukan event): hanya peserta yang kategorinya
     * melewati timing point pos ini yang dikembalikan. Jika pos tidak punya kategori,
     * kembalikan seluruh pendaftar yang sudah punya nomor_registrasi.
     *
     * Tiap peserta juga membawa `scanned_at`: waktu scan PALING AWAL di pos ini bila
     * sudah pernah discan (dari device mana pun), atau null bila belum. Dengan ini,
     * setelah download peserta scanner langsung tahu siapa yang sudah lewat + jamnya.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $assignment = $request->user()?->activeAssignment();

        if (! $assignment || ! $assignment->timingPoint) {
            return response()->json(['message' => 'Belum ditugaskan ke pos.'], 403);
        }

        $timingPoint = $assignment->timingPoint;
        $categoryIds = $timingPoint->categories()->pluck('gtr_categories.id');

        $registrations = GtrRegistration::query()
            ->with(['category:id,name,tag,slug,distance', 'runner:id,first_name,last_name'])
            ->whereNotNull('nomor_registrasi')
            ->when(
                $categoryIds->isNotEmpty(),
                fn ($q) => $q->whereIn('gtr_category_id', $categoryIds),
            )
            ->get();

        // Scan paling awal per peserta DI POS INI (untuk tanda "sudah discan" + waktu).
        $scannedAt = GtrScanLog::query()
            ->where('gtr_timing_point_id', $timingPoint->id)
            ->whereIn('gtr_registration_id', $registrations->pluck('id'))
            ->selectRaw('gtr_registration_id, MIN(scanned_at) as first_scan')
            ->groupBy('gtr_registration_id')
            ->pluck('first_scan', 'gtr_registration_id');

        $rows = $registrations->map(fn (GtrRegistration $r) => [
            'nomor_registrasi' => $r->nomor_registrasi,
            'bib_number' => $r->bib_number,
            'name' => $r->full_name ?: trim(($r->runner?->first_name ?? '') . ' ' . ($r->runner?->last_name ?? '')),
            'gender' => $r->genderCode(),
            'category' => $r->category?->name,
            'scanned_at' => isset($scannedAt[$r->id])
                ? Carbon::parse($scannedAt[$r->id])->toIso8601String()
                : null,
        ])->values();

        return response()->json([
            'timing_point' => [
                'id' => $timingPoint->id,
                'code' => $timingPoint->code,
                'name' => $timingPoint->name,
                'type' => $timingPoint->type,
            ],
            'count' => $rows->count(),
            'scanned_count' => $rows->whereNotNull('scanned_at')->count(),
            'roster' => $rows,
        ]);
    }
}
