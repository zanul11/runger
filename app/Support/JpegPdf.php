<?php

namespace App\Support;

use RuntimeException;

/**
 * Penulis PDF minimal: setiap gambar JPEG menjadi satu halaman penuh (tanpa margin).
 * Data JPEG disisipkan apa adanya (filter DCTDecode), jadi tidak ada kompresi ulang,
 * tidak butuh library tambahan, dan ukuran PDF ≈ total ukuran JPEG.
 */
class JpegPdf
{
    /** 1 px gambar = 0,75 pt, jadi slide 1080×1350 px menjadi halaman 810×1012,5 pt. */
    private const PT_PER_PX = 0.75;

    /**
     * @param  array<int, string>  $jpegPaths  path absolut file JPEG, urut halaman
     */
    public static function build(array $jpegPaths, string $title = ''): string
    {
        if (! $jpegPaths) {
            throw new RuntimeException('Tidak ada halaman untuk PDF.');
        }

        $objects = [];      // nomor objek → isi
        $pageIds = [];
        $next = 3;          // 1 = Catalog, 2 = Pages

        foreach (array_values($jpegPaths) as $i => $path) {
            $info = @getimagesize($path);
            if (! $info || $info[2] !== IMAGETYPE_JPEG) {
                throw new RuntimeException('Bukan file JPEG: ' . basename($path));
            }
            [$w, $h] = $info;
            $channels = $info['channels'] ?? 3;
            $space = match ($channels) { 1 => '/DeviceGray', 4 => '/DeviceCMYK', default => '/DeviceRGB' };
            $decode = $channels === 4 ? ' /Decode [1 0 1 0 1 0 1 0]' : '';
            $data = file_get_contents($path);

            $pw = self::num($w * self::PT_PER_PX);
            $ph = self::num($h * self::PT_PER_PX);
            $img = $next++;
            $content = $next++;
            $page = $next++;
            $name = 'Im' . ($i + 1);

            $objects[$img] = "<< /Type /XObject /Subtype /Image /Width {$w} /Height {$h} /ColorSpace {$space} /BitsPerComponent 8{$decode} /Filter /DCTDecode /Length " . strlen($data) . " >>\nstream\n" . $data . "\nendstream";
            $draw = "q {$pw} 0 0 {$ph} 0 0 cm /{$name} Do Q";
            $objects[$content] = '<< /Length ' . strlen($draw) . " >>\nstream\n" . $draw . "\nendstream";
            $objects[$page] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 {$pw} {$ph}] /Resources << /XObject << /{$name} {$img} 0 R >> >> /Contents {$content} 0 R >>";
            $pageIds[] = $page;
        }

        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[2] = '<< /Type /Pages /Kids [' . implode(' ', array_map(fn ($id) => "{$id} 0 R", $pageIds)) . '] /Count ' . count($pageIds) . ' >>';
        $info = $next;
        $objects[$info] = '<< /Title ' . self::text($title) . ' /Producer (Runger) /CreationDate (D:' . gmdate('YmdHis') . "Z) >>";
        ksort($objects);

        $out = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [];
        foreach ($objects as $id => $body) {
            $offsets[$id] = strlen($out);
            $out .= "{$id} 0 obj\n{$body}\nendobj\n";
        }

        $xref = strlen($out);
        $count = count($objects) + 1;
        $out .= "xref\n0 {$count}\n0000000000 65535 f \n";
        for ($id = 1; $id < $count; $id++) {
            $out .= sprintf("%010d 00000 n \n", $offsets[$id]);
        }
        $out .= "trailer\n<< /Size {$count} /Root 1 0 R /Info {$info} 0 R >>\nstartxref\n{$xref}\n%%EOF\n";

        return $out;
    }

    private static function num(float $v): string
    {
        return rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.');
    }

    /** String PDF literal; teks non-ASCII disimpan sebagai UTF-16BE agar judul tetap benar. */
    private static function text(string $s): string
    {
        if (preg_match('/^[\x20-\x7E]*$/', $s)) {
            return '(' . strtr($s, ['\\' => '\\\\', '(' => '\\(', ')' => '\\)']) . ')';
        }

        return '<FEFF' . strtoupper(bin2hex(mb_convert_encoding($s, 'UTF-16BE', 'UTF-8'))) . '>';
    }
}
