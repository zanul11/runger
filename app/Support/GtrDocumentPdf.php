<?php

namespace App\Support;

use App\Models\GtrDocument;
use App\Models\GtrSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

/**
 * Render dokumen peserta GTR ke PDF A4 (dompdf, tanpa browser) dan publikasikan ke website.
 */
class GtrDocumentPdf
{
    private const DIR = 'gtr/docs';

    /** Batas bawah perkecilan otomatis (persen dari ukuran huruf dokumen). */
    private const MIN_SCALE = 66;

    /**
     * Isi PDF (biner) untuk satu bahasa. Bila isi tidak muat satu halaman A4, ukuran huruf
     * diperkecil bertahap (min. 66%) supaya formulir tetap satu lembar.
     */
    public static function render(GtrDocument $doc, string $lang): string
    {
        $base = max(70, min(140, (int) $doc->font_size ?: 100));
        for ($pct = $base; ; $pct -= 4) {
            $dompdf = self::make($doc, $lang, $pct / 100);
            $dompdf->render();
            if ($dompdf->getCanvas()->get_page_count() <= 1 || $pct - 4 < self::MIN_SCALE) {
                return $dompdf->output();
            }
        }
    }

    private static function make(GtrDocument $doc, string $lang, float $k): \Dompdf\Dompdf
    {
        $fonts = storage_path('fonts');
        File::ensureDirectoryExists($fonts);

        $s = GtrSetting::current();
        $html = view('docs.gtr-document', [
            'doc' => $doc,
            'lang' => $lang,
            'body' => GtrDocumentHtml::toPdf($doc->body($lang), $lang),
            'event' => $s->title ?: 'Gerung Trail Run 2026',
            'eyebrow' => $s->eyebrow,
            'k' => $k,
        ])->render();

        return Pdf::loadHTML($html)
            ->setPaper('a4', 'portrait')
            ->setOption([
                'chroot' => [base_path()],
                'fontDir' => $fonts,
                'fontCache' => $fonts,
                'isRemoteEnabled' => false,
                'isFontSubsettingEnabled' => true,
                'defaultFont' => 'Jakarta',
                'isPhpEnabled' => false,
                'isJavascriptEnabled' => false,
                'dpi' => 96,
            ])
            ->getDomPDF();
    }

    /** Buat PDF semua bahasa lalu tayangkan di website (file lama dihapus). */
    public static function publish(GtrDocument $doc): void
    {
        $disk = Storage::disk('public');
        $stamp = now()->format('YmdHis');
        $paths = ['pdf_id' => null, 'pdf_en' => null];
        foreach ($doc->langs() as $lang) {
            $path = self::DIR . "/{$doc->slug}-{$lang}-{$stamp}.pdf";
            $disk->put($path, self::render($doc, $lang));
            $paths['pdf_' . $lang] = $path;
        }

        $old = array_filter([$doc->pdf_id, $doc->pdf_en]);
        self::saveQuietly($doc, $paths + ['is_published' => true, 'published_at' => now()]);
        foreach ($old as $p) {
            if (! in_array($p, $paths, true)) {
                $disk->delete($p);
            }
        }
    }

    public static function unpublish(GtrDocument $doc): void
    {
        Storage::disk('public')->delete(array_filter([$doc->pdf_id, $doc->pdf_en]));
        self::saveQuietly($doc, ['is_published' => false, 'pdf_id' => null, 'pdf_en' => null, 'published_at' => null]);
    }

    /** Simpan status publikasi tanpa mengubah updated_at (penanda "ada perubahan belum dipublikasikan"). */
    private static function saveQuietly(GtrDocument $doc, array $attrs): void
    {
        $doc->timestamps = false;
        $doc->forceFill($attrs)->save();
        $doc->timestamps = true;
    }

    /**
     * Kelompokkan blok: blok "kiri"/"kanan" yang berurutan jadi satu baris dua kolom,
     * blok "penuh" berdiri sendiri.
     *
     * @return array<int, array{type: 'full'|'cols', blocks?: array, left?: array, right?: array}>
     */
    public static function segments(array $blocks): array
    {
        $out = [];
        foreach ($blocks as $b) {
            if (! is_array($b) || empty($b['type'])) {
                continue;
            }
            $col = $b['data']['column'] ?? 'full';
            if ($col === 'left' || $col === 'right') {
                $last = array_key_last($out);
                if ($last === null || $out[$last]['type'] !== 'cols') {
                    $out[] = ['type' => 'cols', 'left' => [], 'right' => []];
                    $last = array_key_last($out);
                }
                $out[$last][$col][] = $b;
            } else {
                $out[] = ['type' => 'full', 'blocks' => [$b]];
            }
        }

        return $out;
    }

    /** Teks multi-baris → array baris tidak kosong. */
    public static function lines(?string $text): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $text)), fn ($l) => $l !== ''));
    }

    /** Baris berisi isian sebaris (___) atau kotak centang ([ ]). */
    public static function isInline(string $line): bool
    {
        return (bool) preg_match('/_{3,}|\[\s?\]/', $line);
    }

    /**
     * Ubah ___ menjadi garis isian dan [ ] menjadi kotak centang (teks lain di-escape).
     * Kotak centang + label pendek sesudahnya dijaga satu baris (spasi tak terputus — dompdf
     * tidak menangani white-space:nowrap pada elemen inline dengan baik).
     */
    public static function inline(string $text): HtmlString
    {
        $esc = fn (string $t) => str_replace('  ', "\u{00A0} ", e($t));
        $box = '<span class="cb"></span>';
        $nb = fn (string $t) => str_replace(' ', "\u{00A0}", $t);
        $html = '';
        $pending = false;
        foreach (preg_split('/(_{3,}|\[\s?\])/', $text, -1, PREG_SPLIT_DELIM_CAPTURE) as $p) {
            if (preg_match('/^_{3,}$/', $p)) {
                if ($pending) {
                    $html .= $box;
                    $pending = false;
                }
                $html .= '<span class="bl" style="width:' . max(10, min(70, strlen($p) * 2.2)) . 'mm"></span>';
            } elseif (preg_match('/^\[\s?\]$/', $p)) {
                if ($pending) {
                    $html .= $box;
                }
                $pending = true;
            } elseif ($pending) {
                $lead = rtrim($p);
                $trail = substr($p, strlen($lead));
                if (mb_strlen(trim($lead)) > 32 && preg_match('/^(\s*\S+)(.*)$/su', $lead, $m)) {
                    [$lead, $trail] = [$m[1], $m[2] . $trail];
                }
                $html .= $box . $nb(e(ltrim($lead))) . $esc($trail);
                $pending = false;
            } else {
                $html .= $esc($p);
            }
        }
        if ($pending) {
            $html .= $box;
        }

        return new HtmlString($html);
    }

}
