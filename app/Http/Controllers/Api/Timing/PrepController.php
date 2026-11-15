<?php

namespace App\Http\Controllers\Api\Timing;

use App\Casts\DateTimeMs;
use App\Http\Controllers\Controller;
use App\Models\GtrPrepItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Checklist persiapan panitia (GTR Crew → Persiapan). Semua akun GTR Crew bisa melihat;
 * centang, tambah, ubah, hapus & isi template hanya role panitia (dan admin).
 * Versi publik baca-saja: App\Http\Controllers\GtrPrepPublicController.
 */
class PrepController extends Controller
{
    /** GET /prep */
    public function index(): JsonResponse
    {
        $items = GtrPrepItem::with('doneBy:id,name')->orderBy('sort_order')->orderBy('id')->get();

        return response()->json([
            'items' => $items->map(fn (GtrPrepItem $i) => $this->row($i))->values(),
            // Urutan kategori = urutan kemunculan pertama.
            'categories' => $items->pluck('category')->unique()->values(),
            'phases' => GtrPrepItem::PHASES,
            'total' => $items->count(),
            'done' => $items->whereNotNull('done_at')->count(),
        ]);
    }

    /** POST /prep/{item}/check — Body: done (bool). */
    public function check(Request $request, GtrPrepItem $item): JsonResponse
    {
        $done = (bool) $request->validate(['done' => ['required', 'boolean']])['done'];

        $item->forceFill($done
            ? ['done_at' => $item->done_at ?? now(), 'done_by' => $item->done_by ?? $request->user()->id]
            : ['done_at' => null, 'done_by' => null])->save();

        return response()->json(['item' => $this->row($item->fresh('doneBy:id,name'))]);
    }

    /** POST /prep (panitia / admin) */
    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $data['sort_order'] = (int) GtrPrepItem::max('sort_order') + 10;
        $item = GtrPrepItem::create($data);

        return response()->json(['item' => $this->row($item)], 201);
    }

    /** PUT /prep/{item} (panitia / admin) */
    public function update(Request $request, GtrPrepItem $item): JsonResponse
    {
        $item->update($this->validated($request));

        return response()->json(['item' => $this->row($item->fresh('doneBy:id,name'))]);
    }

    /** DELETE /prep/{item} (panitia / admin) */
    public function destroy(GtrPrepItem $item): JsonResponse
    {
        $item->delete();

        return response()->json(['message' => 'Item dihapus.']);
    }

    /** POST /prep/template (panitia / admin) — tambah item template yang belum ada (aman diulang). */
    public function template(): JsonResponse
    {
        $n = GtrPrepItem::fillTemplate();

        return response()->json([
            'added' => $n,
            'message' => $n ? "{$n} item template ditambahkan." : 'Semua item template sudah ada di checklist.',
        ]);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'category' => ['required', 'string', 'max:64'],
            'title' => ['required', 'string', 'max:255'],
            'qty' => ['nullable', 'string', 'max:64'],
            'pic' => ['nullable', 'string', 'max:120'],
            'location' => ['nullable', 'string', 'max:120'],
            'phase' => ['nullable', Rule::in(array_keys(GtrPrepItem::PHASES))],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    private function row(GtrPrepItem $i): array
    {
        return [
            'id' => $i->id,
            'category' => $i->category,
            'title' => $i->title,
            'qty' => $i->qty,
            'pic' => $i->pic,
            'location' => $i->location,
            'phase' => $i->phase,
            'notes' => $i->notes,
            'done' => $i->done_at !== null,
            'done_at_ms' => DateTimeMs::toMs($i->done_at),
            'done_by' => $i->doneBy?->name,
        ];
    }
}
