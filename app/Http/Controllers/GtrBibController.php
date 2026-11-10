<?php

namespace App\Http\Controllers;

use App\Models\GtrCategory;
use App\Models\GtrRegistration;
use App\Support\GtrBibPdf;
use Illuminate\Http\Request;

/** Cetak BIB GTR (PDF A5 landscape, 1 BIB per halaman) — khusus admin. */
class GtrBibController extends Controller
{
    public function pdf(Request $request)
    {
        abort_unless($request->user()?->isAdmin(), 403);
        $data = $request->validate([
            'category' => ['nullable', 'string'],
            'bib' => ['nullable', 'string', 'max:20'],
            'sample' => ['nullable', 'boolean'],
            'flip' => ['nullable', 'boolean'],
        ]);

        $q = GtrRegistration::with('category')
            ->where('payment_status', 'paid')
            ->whereNotNull('bib_number')
            ->where('bib_number', '!=', '');

        $cat = null;
        if (! empty($data['category']) && $data['category'] !== 'all') {
            $cat = GtrCategory::findOrFail($data['category']);
            $q->where('gtr_category_id', $cat->id);
        }
        if (! empty($data['bib'])) {
            $q->where('bib_number', trim($data['bib']));
        }

        $regs = $q->get()
            ->sortBy(fn ($r) => [$r->category?->sort_order ?? 0, (int) $r->bib_number, $r->bib_number])
            ->values();
        if (! empty($data['sample'])) {
            $regs = $regs->unique('gtr_category_id')->values();
        }
        abort_if($regs->isEmpty(), 404, 'Tidak ada peserta lunas ber-BIB yang cocok.');

        $name = 'BIB GTR 2026 - ' . (! empty($data['bib']) ? 'BIB ' . $data['bib'] : ($cat ? $cat->name : 'Semua')) . (! empty($data['sample']) ? ' (contoh)' : '') . '.pdf';

        // Default: panel elevasi dibalik 180° agar terbaca pelari saat menunduk.
        $flip = ! array_key_exists('flip', $data) || $data['flip'] === null || (bool) $data['flip'];

        return response(GtrBibPdf::render($regs, $flip), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $name . '"',
            'Cache-Control' => 'no-store',
        ]);
    }
}
