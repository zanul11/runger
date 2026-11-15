<?php

namespace App\Http\Controllers;

use App\Models\GtrPrepItem;
use Illuminate\View\View;

/**
 * Checklist persiapan panitia GTR — halaman publik BACA-SAJA (tanpa login), untuk dibagikan
 * ke seluruh panitia. Perubahan hanya lewat aplikasi GTR Crew (/crew) dengan akun panitia.
 * Tidak diindeks mesin pencari (noindex).
 */
class GtrPrepPublicController extends Controller
{
    public function index(): View
    {
        $items = GtrPrepItem::with('doneBy:id,name')->orderBy('sort_order')->orderBy('id')->get();

        return view('pages.gtr-persiapan', [
            'active' => null,
            'groups' => $items->groupBy('category'),
            'total' => $items->count(),
            'done' => $items->whereNotNull('done_at')->count(),
            'phases' => GtrPrepItem::PHASES,
            'updatedAt' => $items->max('updated_at'),
        ]);
    }
}
