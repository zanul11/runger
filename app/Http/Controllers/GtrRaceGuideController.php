<?php

namespace App\Http\Controllers;

use App\Models\GtrSetting;
use App\Support\GtrRaceGuide;
use App\Support\JpegPdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

/**
 * Race Guide GTR: generator di admin (render slide di browser → upload JPG per slide → publikasi),
 * serta halaman publik untuk melihat & mengunduh race guide yang sudah final.
 */
class GtrRaceGuideController extends Controller
{
    private const DIR = 'gtr/race-guide';
    private const PDF = 'gtr-race-guide.pdf';

    /**
     * Path PDF race guide yang tayang. Race guide yang dipublikasikan sebelum ada fitur PDF
     * dibuatkan PDF-nya sekali dari slide yang tersimpan.
     */
    private function pdfPath(GtrSetting $s): ?string
    {
        $slides = $s->race_guide_slides ?? [];
        if (! $s->raceGuidePublished() || ! $slides) {
            return null;
        }

        $disk = Storage::disk('public');
        $path = dirname($slides[0]) . '/' . self::PDF;
        if ($disk->exists($path)) {
            return $path;
        }

        $files = array_values(array_filter($slides, fn ($f) => $disk->exists($f)));
        try {
            $disk->put($path, JpegPdf::build(array_map(fn ($f) => $disk->path($f), $files), 'Race Guide - Gerung Trail Run'));

            return $path;
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    // ===== Admin =====

    /** Generator & publikasi khusus admin (marshal juga bisa login, tapi tidak boleh di sini). */
    private function ensureAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403);
    }

    public function generator(Request $request)
    {
        $this->ensureAdmin($request);

        return view('admin.gtr-race-guide', [
            'g' => GtrRaceGuide::build(),
            'pub' => GtrSetting::current(),
        ]);
    }

    /** Simpan satu slide hasil render (dipanggil berurutan per slide agar tidak kena batas ukuran POST). */
    public function upload(Request $request): JsonResponse
    {
        $this->ensureAdmin($request);
        $data = $request->validate([
            'batch' => ['required', 'regex:/^[A-Za-z0-9]{8,40}$/'],
            'index' => ['required', 'integer', 'min:0', 'max:98'],
            'name' => ['required', 'regex:/^[a-z0-9-]{1,60}$/'],
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png', 'max:8192'],
        ]);

        $file = sprintf('%02d-%s.jpg', $data['index'] + 1, $data['name']);
        $path = $request->file('file')->storeAs(self::DIR . '/' . $data['batch'], $file, 'public');

        return response()->json(['path' => $path]);
    }

    /** Jadikan satu batch slide sebagai race guide resmi di website + buat ZIP-nya. */
    public function publish(Request $request): JsonResponse
    {
        $this->ensureAdmin($request);
        $data = $request->validate([
            'batch' => ['required', 'regex:/^[A-Za-z0-9]{8,40}$/'],
            'files' => ['required', 'array', 'min:1', 'max:99'],
            'files.*' => ['required', 'string'],
        ]);

        $disk = Storage::disk('public');
        $dir = self::DIR . '/' . $data['batch'];
        foreach ($data['files'] as $f) {
            if (dirname($f) !== $dir || ! $disk->exists($f)) {
                return response()->json(['message' => 'File slide tidak valid: ' . $f], 422);
            }
        }

        $zip = $dir . '/gtr-race-guide.zip';
        $za = new ZipArchive();
        if ($za->open($disk->path($zip), ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return response()->json(['message' => 'Gagal membuat file ZIP.'], 500);
        }
        foreach ($data['files'] as $f) {
            $za->addFile($disk->path($f), basename($f));
        }
        $za->close();

        // PDF (satu slide per halaman) untuk ditampilkan & diunduh di website.
        try {
            $disk->put($dir . '/' . self::PDF, JpegPdf::build(array_map(fn ($f) => $disk->path($f), $data['files']), 'Race Guide - Gerung Trail Run'));
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'Gagal membuat PDF: ' . $e->getMessage()], 500);
        }

        $s = GtrSetting::current();
        $s->update([
            'race_guide_slides' => array_values($data['files']),
            'race_guide_zip' => $zip,
            'race_guide_published_at' => now(),
        ]);

        // Hapus batch lama / batch gagal agar storage tidak menumpuk.
        foreach ($disk->directories(self::DIR) as $d) {
            if ($d !== $dir) {
                $disk->deleteDirectory($d);
            }
        }

        return response()->json(['ok' => true, 'count' => count($data['files']), 'url' => route('gtr.guide')]);
    }

    /** Tarik race guide dari website (file dihapus). */
    public function unpublish(Request $request): JsonResponse
    {
        $this->ensureAdmin($request);
        GtrSetting::current()->update([
            'race_guide_slides' => null,
            'race_guide_zip' => null,
            'race_guide_published_at' => null,
        ]);
        Storage::disk('public')->deleteDirectory(self::DIR);

        return response()->json(['ok' => true]);
    }

    // ===== Publik =====

    public function index()
    {
        $s = GtrSetting::current();
        $pdf = $this->pdfPath($s);

        return view('pages.gtr-race-guide', [
            'active' => 'guide',
            'published' => $s->raceGuidePublished(),
            'slides' => collect($s->race_guide_slides ?? [])
                ->filter(fn ($p) => Storage::disk('public')->exists($p))
                ->map(fn ($p) => ['url' => Storage::disk('public')->url($p), 'name' => basename($p)])
                ->values(),
            'updatedAt' => $s->race_guide_published_at,
            'hasZip' => $s->race_guide_zip && Storage::disk('public')->exists($s->race_guide_zip),
            // Path relatif agar PDF.js selalu memuat dari origin yang sama (www / non-www, http / https).
            'pdfUrl' => $pdf ? parse_url(Storage::disk('public')->url($pdf), PHP_URL_PATH) . '?v=' . $s->race_guide_published_at?->timestamp : null,
        ]);
    }

    public function download()
    {
        $s = GtrSetting::current();
        abort_unless($s->raceGuidePublished() && $s->race_guide_zip && Storage::disk('public')->exists($s->race_guide_zip), 404);

        return Storage::disk('public')->download($s->race_guide_zip, 'Race Guide - Gerung Trail Run.zip');
    }

    public function downloadPdf()
    {
        $pdf = $this->pdfPath(GtrSetting::current());
        abort_unless($pdf, 404);

        return Storage::disk('public')->download($pdf, 'Race Guide - Gerung Trail Run.pdf', ['Content-Type' => 'application/pdf']);
    }
}
