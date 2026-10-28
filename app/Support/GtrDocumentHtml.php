<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

/**
 * Konversi isi dokumen peserta GTR:
 *  - fromBlocks(): format blok lama (Filament Builder) → HTML editor (dipakai migrasi & isi awal).
 *  - toPdf():      HTML dari RichEditor → HTML yang aman untuk dompdf (CSS 2.1, berbasis tabel).
 *
 * Penanda khusus di editor:
 *   ___            garis isian; paragraf yang diakhiri ___ diberi garis sampai ujung baris
 *   [ ]            kotak centang
 *   [ttd]          kotak tanda tangan (paragraf tersendiri)
 *   "Tanya? [ ] Ya [ ] Tidak"  → pertanyaan dengan pilihan rata kanan
 *   "[ ] A  [ ] B  [ ] C"      → pilihan ditata dalam grid 2–3 kolom
 *   Grid (kolom)   → tata letak kolom;  Kutipan (blockquote) → kotak bergaris
 */
class GtrDocumentHtml
{
    private const SIGN_TOKEN = '/^\[\s*(ttd|tanda\s*tangan|signature|sign)\s*\]$/i';

    // ===================== Blok lama → HTML editor =====================

    public static function fromBlocks(array $blocks, string $lang): string
    {
        $html = '';
        foreach (GtrDocumentPdf::segments($blocks) as $seg) {
            if ($seg['type'] === 'cols') {
                $html .= self::grid([
                    implode('', array_map(fn ($b) => self::block($b, $lang), $seg['left'])),
                    implode('', array_map(fn ($b) => self::block($b, $lang), $seg['right'])),
                ]);
            } else {
                foreach ($seg['blocks'] as $b) {
                    $html .= self::block($b, $lang);
                }
            }
        }

        return $html;
    }

    private static function grid(array $cols): string
    {
        $n = count($cols);
        $html = '<div class="grid-layout" data-cols="' . $n . '" data-from-breakpoint="lg" style="--cols: repeat(' . $n . ', minmax(0, 1fr))">';
        foreach ($cols as $c) {
            $html .= '<div class="grid-layout-col" data-col-span="1" style="--col-span: span 1 / span 1">' . ($c ?: '<p></p>') . '</div>';
        }

        return $html . '</div>';
    }

    private static function block(array $b, string $lang): string
    {
        $d = $b['data'] ?? [];
        $lines = fn ($t) => GtrDocumentPdf::lines($t);

        switch ($b['type'] ?? '') {
            case 'heading':
                return '<h2>' . e($d['text'] ?? '') . '</h2>';

            case 'paragraph':
                $out = '';
                foreach (preg_split('/\n\s*\n/', str_replace("\r\n", "\n", (string) ($d['text'] ?? ''))) as $para) {
                    if (trim($para) !== '') {
                        $out .= '<p style="text-align: justify">' . nl2br(e(trim($para)), false) . '</p>';
                    }
                }

                return $out;

            case 'statements':
                $tag = ($d['style'] ?? 'number') === 'bullet' ? 'ul' : 'ol';

                return '<' . $tag . '>' . implode('', array_map(fn ($l) => '<li><p>' . e($l) . '</p></li>', $lines($d['items'] ?? ''))) . '</' . $tag . '>';

            case 'fields':
                $center = ($d['align'] ?? 'left') === 'center';

                return implode('', array_map(function ($l) use ($center) {
                    if (GtrDocumentPdf::isInline($l)) {
                        return '<p>' . e($l) . '</p>';
                    }

                    return $center
                        ? '<p style="text-align: center">' . e($l) . ' ______________________________</p>'
                        : '<p>' . e($l) . ' ___</p>';
                }, $lines($d['items'] ?? '')));

            case 'checks':
                $out = ! empty($d['label']) ? '<p><strong>' . e($d['label']) . '</strong></p>' : '';
                $opts = array_map(fn ($o) => '[ ] ' . $o, $lines($d['options'] ?? ''));

                return $out . '<p>' . e(implode('     ', $opts)) . '</p>';

            case 'yesno':
                [$y, $n] = $lang === 'en' ? ['Yes', 'No'] : ['Ya', 'Tidak'];

                return implode('', array_map(fn ($q) => '<p>' . e($q) . " [ ] {$y}   [ ] {$n}</p>", $lines($d['questions'] ?? '')));

            case 'signature':
                $panels = array_values(array_filter($d['panels'] ?? [], 'is_array'));
                $boxes = array_map(function ($p) use ($lines) {
                    $h = ! empty($p['title']) ? '<h3>' . e($p['title']) . '</h3>' : '';
                    foreach ($lines($p['fields'] ?? '') as $l) {
                        $h .= '<p>' . e($l) . (GtrDocumentPdf::isInline($l) ? '' : ' ___') . '</p>';
                    }
                    $h .= '<p>' . e($p['sign_label'] ?? '') . '</p><p>[ttd]</p>';
                    if (! empty($p['note'])) {
                        $h .= '<p><em>' . e($p['note']) . '</em></p>';
                    }

                    return '<blockquote>' . $h . '</blockquote>';
                }, $panels);

                return count($boxes) > 1 ? self::grid($boxes) : implode('', $boxes);
        }

        return '';
    }

    // ===================== HTML editor → HTML dompdf =====================

    public static function toPdf(?string $html, string $lang): string
    {
        $html = trim((string) $html);
        if ($html === '') {
            return '';
        }

        $dom = new DOMDocument('1.0', 'UTF-8');
        $prev = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8"><div id="gtr-root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
        $xp = new DOMXPath($dom);
        $root = $xp->query('//div[@id="gtr-root"]')->item(0);

        // Elemen yang tidak relevan untuk dokumen cetak.
        foreach (iterator_to_array($xp->query('.//script|.//style|.//iframe|.//img|.//figure|.//object|.//embed', $root)) as $n) {
            $n->parentNode->removeChild($n);
        }

        // Grid kolom → tabel.
        foreach (array_reverse(iterator_to_array($xp->query('.//div[contains(concat(" ", normalize-space(@class), " "), " grid-layout ")]', $root))) as $grid) {
            $cols = [];
            foreach ($grid->childNodes as $c) {
                if ($c instanceof DOMElement && str_contains(' ' . $c->getAttribute('class') . ' ', ' grid-layout-col ')) {
                    $cols[] = ['span' => max(1, (int) $c->getAttribute('data-col-span') ?: 1), 'html' => self::inner($c)];
                }
            }
            if (! $cols) {
                $grid->parentNode->removeChild($grid);

                continue;
            }
            $total = array_sum(array_column($cols, 'span'));
            $cells = '';
            foreach ($cols as $i => $c) {
                $pad = ($i > 0 ? 'padding-left:2.2mm;' : '') . ($i < count($cols) - 1 ? 'padding-right:2.2mm;' : '');
                $cells .= '<td class="c" style="width:' . round($c['span'] / $total * 100, 2) . '%;' . $pad . '">' . $c['html'] . '</td>';
            }
            self::replace($dom, $grid, '<table class="cols"><tr>' . $cells . '</tr></table>');
        }

        // Daftar bernomor / poin → tabel dengan lencana nomor / kotak merah.
        foreach (array_reverse(iterator_to_array($xp->query('.//ol|.//ul', $root))) as $list) {
            $ordered = strtolower($list->nodeName) === 'ol';
            $n = max(1, (int) ($list->getAttribute('start') ?: 1));
            $rows = '';
            foreach ($list->childNodes as $li) {
                if ($li instanceof DOMElement && strtolower($li->nodeName) === 'li') {
                    $mark = $ordered ? '<div class="num">' . $n++ . '</div>' : '<div class="dot"></div>';
                    $rows .= '<tr><td class="n">' . $mark . '</td><td class="t">' . self::inner($li) . '</td></tr>';
                }
            }
            self::replace($dom, $list, '<table class="st">' . $rows . '</table>');
        }

        // Kutipan → kotak bergaris.
        foreach (iterator_to_array($xp->query('.//blockquote', $root)) as $bq) {
            self::replace($dom, $bq, '<div class="box">' . self::inner($bq) . '</div>');
        }

        // Tabel buatan admin.
        foreach (iterator_to_array($xp->query('.//table[not(@class)]', $root)) as $t) {
            $t->setAttribute('class', 'ut');
        }

        // Paragraf khusus: kotak tanda tangan, pertanyaan Ya/Tidak, label + garis isian.
        foreach (iterator_to_array($xp->query('.//p', $root)) as $p) {
            $text = trim(preg_replace('/\s+/u', ' ', str_replace("\u{00A0}", ' ', $p->textContent)));
            $align = preg_match('/text-align:\s*(center|right|end)/i', $p->getAttribute('style'));

            if (preg_match(self::SIGN_TOKEN, $text)) {
                self::replace($dom, $p, '<div class="sb"></div>');
            } elseif (preg_match('/^\[\s?\]/', $text) && preg_match_all('/\[\s?\]/', $text) >= 2) {
                // Deretan pilihan "[ ] A [ ] B [ ] C" → grid pilihan (dompdf tidak bisa melipat baris pilihan panjang).
                $opts = array_values(array_filter(array_map('trim', preg_split('/\[\s?\]/', $text)), fn ($o) => $o !== ''));
                $per = min(count($opts), self::inside($p, 'c') ? 2 : 3);
                $rows = '';
                foreach (array_chunk($opts, $per) as $row) {
                    $rows .= '<tr>';
                    foreach ($row as $o) {
                        $rows .= '<td style="width:' . round(100 / $per, 2) . '%">' . GtrDocumentPdf::inline('[ ] ' . $o) . '</td>';
                    }
                    $rows .= str_repeat('<td></td>', $per - count($row)) . '</tr>';
                }
                self::replace($dom, $p, '<table class="og">' . $rows . '</table>');
            } elseif (preg_match('/^(.+?\?)\s*(\[\s?\].*)$/u', $text, $m)) {
                self::replace($dom, $p, '<table class="yn"><tr><td class="q">' . GtrDocumentPdf::inline($m[1]) . '</td><td class="a">' . GtrDocumentPdf::inline($m[2]) . '</td></tr></table>');
            } elseif (! $align && preg_match('/_{3,}$/', $text)) {
                $label = preg_replace('/\s*_{3,}\s*((?:<\/[a-z]+>\s*)*)$/i', '$1', self::inner($p));
                self::replace($dom, $p, '<table class="fl"><tr><td class="lb">' . $label . '</td><td class="ln">&#160;</td></tr></table>');
            } elseif ($text === '' && ! $xp->query('.//br', $p)->length) {
                $p->setAttribute('class', 'gap');
            }
        }

        // Judul.
        foreach (iterator_to_array($xp->query('.//h1|.//h2', $root)) as $h) {
            self::replace($dom, $h, '<div class="h">' . self::inner($h) . '</div>');
        }
        foreach (iterator_to_array($xp->query('.//h3|.//h4|.//h5|.//h6', $root)) as $h) {
            self::replace($dom, $h, '<div class="h3">' . self::inner($h) . '</div>');
        }

        // Penanda ___ dan [ ] di semua teks yang tersisa.
        foreach (iterator_to_array($xp->query('.//text()', $root)) as $t) {
            if (preg_match('/_{3,}|\[\s?\]/', $t->nodeValue) && ! self::inside($t, 'bl')) {
                self::replace($dom, $t, (string) GtrDocumentPdf::inline($t->nodeValue));
            }
        }

        return self::inner($root);
    }

    private static function inner(DOMNode $node): string
    {
        $html = '';
        foreach ($node->childNodes as $c) {
            $html .= $node->ownerDocument->saveHTML($c);
        }

        return $html;
    }

    /** Ganti node dengan potongan HTML. */
    private static function replace(DOMDocument $dom, DOMNode $node, string $html): void
    {
        $tmp = new DOMDocument('1.0', 'UTF-8');
        $prev = libxml_use_internal_errors(true);
        $tmp->loadHTML('<?xml encoding="UTF-8"><div id="frag">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
        $wrap = (new DOMXPath($tmp))->query('//div[@id="frag"]')->item(0);
        $parent = $node->parentNode;
        foreach (iterator_to_array($wrap->childNodes) as $c) {
            $parent->insertBefore($dom->importNode($c, true), $node);
        }
        $parent->removeChild($node);
    }

    private static function inside(DOMNode $node, string $class): bool
    {
        for ($n = $node->parentNode; $n instanceof DOMElement; $n = $n->parentNode) {
            if (str_contains(' ' . $n->getAttribute('class') . ' ', " {$class} ")) {
                return true;
            }
        }

        return false;
    }
}
