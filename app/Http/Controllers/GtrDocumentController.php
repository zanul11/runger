<?php

namespace App\Http\Controllers;

use App\Models\GtrDocument;
use App\Support\GtrDocumentPdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/** Dokumen peserta GTR: pratinjau untuk admin & unduhan PDF yang sudah dipublikasikan. */
class GtrDocumentController extends Controller
{
    /** Pratinjau PDF dari isi tersimpan (belum tentu dipublikasikan) — khusus admin. */
    public function preview(Request $request, GtrDocument $document, string $lang)
    {
        abort_unless($request->user()?->isAdmin(), 403);
        abort_unless(in_array($lang, $document->langs(), true), 404);

        return response(GtrDocumentPdf::render($document, $lang), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $document->downloadName($lang) . '"',
            'Cache-Control' => 'no-store',
        ]);
    }

    /** Unduh PDF versi yang tayang di website. */
    public function download(string $slug, string $lang)
    {
        $doc = GtrDocument::where('slug', $slug)->where('is_published', true)->firstOrFail();
        $path = $lang === 'en' ? $doc->pdf_en : $doc->pdf_id;
        abort_unless($path && Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->download($path, $doc->downloadName($lang), ['Content-Type' => 'application/pdf']);
    }
}
