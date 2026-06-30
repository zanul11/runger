<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\GtrRegistration;
use Illuminate\Http\JsonResponse;

class RosterController extends Controller
{
    /**
     * Roster peserta untuk diunduh & dipakai offline oleh scanner.
     *
     * Catatan: kategori GTR (gtr_categories) belum terikat ke events, jadi roster
     * untuk event GTR = seluruh pendaftar GTR yang sudah punya qr_token. Parameter
     * {event} divalidasi agar route konsisten dengan FASE 3 (event GTR).
     */
    public function __invoke(Event $event): JsonResponse
    {
        $rows = GtrRegistration::query()
            ->with(['category:id,name,tag,slug,distance', 'runner:id,first_name,last_name'])
            ->whereNotNull('qr_token')
            ->get()
            ->map(fn (GtrRegistration $r) => [
                'qr_token' => $r->qr_token,
                'bib_number' => $r->bib_number,
                'name' => $r->full_name ?: trim(($r->runner?->first_name ?? '') . ' ' . ($r->runner?->last_name ?? '')),
                'gender' => $r->genderCode(),
                'category' => $r->category?->tag ?: $r->category?->distance,
                'category_slug' => $r->category?->slug,
            ])
            ->values();

        return response()->json([
            'event_id' => $event->id,
            'count' => $rows->count(),
            'roster' => $rows,
        ]);
    }
}
