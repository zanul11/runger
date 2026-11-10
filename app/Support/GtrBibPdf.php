<?php

namespace App\Support;

use App\Models\GtrCategory;
use App\Models\GtrRegistration;
use App\Models\GtrSetting;
use App\Models\GtrSponsor;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * BIB peserta GTR siap cetak: A5 landscape (210 × 148 mm), satu BIB per halaman.
 *  - Bagian atas (putih): logo, SOS panitia, kategori, gender, nomor BIB, nama, sponsor,
 *    ukuran jersey, golongan darah, cek medali/refreshment, kontak keluarga.
 *  - Bagian bawah: panel profil elevasi berwarna kategori (lengkap dengan ketinggian, km,
 *    titik WS/CP/COP, puncak, start, gain & COT) — dicetak terbalik 180° agar terbaca
 *    oleh pelari saat menunduk.
 * Panel elevasi digambar dengan GD (PNG 300 dpi, font GTR) karena dompdf tidak bisa
 * merender teks SVG, gradasi, maupun rotasi dengan andal.
 */
class GtrBibPdf
{
    public const W = 210;
    public const H = 148;
    /** Tinggi panel elevasi (mm), menempel di bawah. */
    public const PANEL_H = 56;

    public const TYPE_COLOR = ['ws' => '#3B82F6', 'cp' => '#F59E0B', 'cop' => '#FFFFFF'];

    private const DPI = 300;
    private const SUPERSAMPLE = 2;
    private const VERSION = 'v4';

    /** @param  Collection<int, GtrRegistration>  $regs */
    public static function render(Collection $regs, bool $flip = true): string
    {
        $fonts = storage_path('fonts');
        File::ensureDirectoryExists($fonts);
        @set_time_limit(300);
        // ±1 MB memori per BIB saat dirender sekaligus (266 BIB ≈ 250 MB).
        if (self::bytes(ini_get('memory_limit')) !== -1 && self::bytes(ini_get('memory_limit')) < 512 * 1048576) {
            @ini_set('memory_limit', '512M');
        }

        $s = GtrSetting::current();
        $cats = $regs->pluck('category')->filter()->unique('id');
        $themes = $cats->mapWithKeys(fn (GtrCategory $c) => [$c->id => self::theme($c)]);
        $panels = $cats->mapWithKeys(fn (GtrCategory $c) => [$c->id => self::panel($c, $flip)]);

        $html = view('docs.gtr-bib', [
            'regs' => $regs,
            'themes' => $themes,
            'panels' => $panels,
            'sponsors' => self::sponsors(),
            'logos' => [
                'gtr' => self::flat(public_path('assets/gtr/logo.png'), 480),
                'runger' => self::flat(public_path('assets/gtr/logo-dark.png'), 600),
            ],
            'event' => $s->title ?: 'Gerung Trail Run 2026',
            'eyebrow' => $s->eyebrow,
            'raceContact' => self::phone($s->race_contact ?: $s->rpc_contact),
        ])->render();

        return Pdf::loadHTML($html)
            ->setPaper([0, 0, 595.28, 419.53]) // A5 landscape dalam pt
            ->setOption([
                'chroot' => [base_path()],
                'fontDir' => $fonts,
                'fontCache' => $fonts,
                'isRemoteEnabled' => false,
                'isPhpEnabled' => false,
                'isJavascriptEnabled' => false,
                'isFontSubsettingEnabled' => true,
                'defaultFont' => 'Jakarta',
                'dpi' => 96,
            ])
            ->output();
    }

    // ===================== Data & warna =====================

    /** Nomor HP rapi: 6287864254019 → 0878-6425-4019 (teks lain dibiarkan). */
    public static function phone(?string $raw): ?string
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return null;
        }
        if (! preg_match('/\+?[\d][\d\s.\-]{7,}\d/', $raw, $m)) {
            return $raw;
        }
        $d = preg_replace('/\D/', '', $m[0]);
        if (str_starts_with($d, '62')) {
            $d = '0' . substr($d, 2);
        }

        return implode('-', array_filter([substr($d, 0, 4), substr($d, 4, 4), substr($d, 8)]));
    }

    /**
     * Warna kategori untuk cetak: `ink` (nomor BIB di atas putih) & `panel` (latar elevasi)
     * digelapkan otomatis bila warna asli terlalu terang (mis. hijau neon 7K).
     */
    public static function theme(GtrCategory $c): array
    {
        $base = self::rgb($c->color ?: '#E53935');

        return [
            'base' => self::hex($base),
            'ink' => self::hex(self::darkenTo($base, 0.16)),
            'panel' => self::hex(self::darkenTo($base, 0.12)),
        ];
    }

    private static function rgb(string $hex): array
    {
        $hex = ltrim(trim($hex), '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        $hex = str_pad(substr(preg_replace('/[^0-9a-f]/i', '', $hex), 0, 6), 6, '0');

        return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    }

    private static function hex(array $c): string
    {
        return sprintf('#%02X%02X%02X', ...array_map(fn ($v) => max(0, min(255, (int) round($v))), $c));
    }

    private static function luminance(array $c): float
    {
        $l = array_map(function ($v) {
            $v /= 255;

            return $v <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4;
        }, $c);

        return 0.2126 * $l[0] + 0.7152 * $l[1] + 0.0722 * $l[2];
    }

    /** Gelapkan warna sampai luminansinya ≤ target (warna yang sudah gelap dibiarkan). */
    private static function darkenTo(array $c, float $target): array
    {
        for ($i = 0; $i < 40 && self::luminance($c) > $target; $i++) {
            $c = array_map(fn ($v) => $v * 0.94, $c);
        }

        return $c;
    }

    /**
     * Logo sponsor aktif sebagai PNG lokal kecil tanpa transparansi (lihat flat()).
     *
     * @return array<int, array{name: string, path: string, ratio: float}>
     */
    public static function sponsors(): array
    {
        $disk = Storage::disk('public');
        $dir = storage_path('app/bib-cache');
        File::ensureDirectoryExists($dir);
        $out = [];
        foreach (GtrSponsor::where('is_active', true)->orderBy('sort_order')->orderBy('id')->get() as $sp) {
            if (! $sp->logo || ! $disk->exists($sp->logo)) {
                continue;
            }
            $src = $disk->path($sp->logo);
            $info = @getimagesize($src);
            if (! $info || $info[0] < 2 || $info[1] < 2) {
                continue;
            }
            $path = self::flat($src, 480);
            if (! $path) {
                continue;
            }
            $out[] = ['name' => $sp->name, 'path' => $path, 'ratio' => $info[0] / $info[1]];
        }

        return $out;
    }

    /**
     * Salinan gambar yang ringan untuk dompdf: diperkecil (maks. $maxW px) dan diratakan di atas
     * latar putih tanpa kanal alpha — dompdf memproses PNG transparan piksel demi piksel dan
     * sangat lambat. Hasil di-cache per file sumber.
     */
    public static function flat(string $src, int $maxW = 600): ?string
    {
        if (! is_file($src)) {
            return null;
        }
        $dir = storage_path('app/bib-cache');
        File::ensureDirectoryExists($dir);
        $out = $dir . '/img-' . md5($src . '|' . filemtime($src) . '|' . $maxW) . '.png';
        if (is_file($out)) {
            return $out;
        }
        $im = @imagecreatefromstring((string) file_get_contents($src));
        if (! $im) {
            return null;
        }
        [$w, $h] = [imagesx($im), imagesy($im)];
        $nw = min($w, $maxW);
        $nh = max(1, (int) round($h * $nw / $w));
        $dst = imagecreatetruecolor($nw, $nh);
        imagefilledrectangle($dst, 0, 0, $nw, $nh, imagecolorallocate($dst, 255, 255, 255));
        imagealphablending($dst, true);
        imagecopyresampled($dst, $im, 0, 0, 0, 0, $nw, $nh, $w, $h);
        imagepng($dst, $out, 6);
        imagedestroy($im);
        imagedestroy($dst);

        return $out;
    }

    // ===================== QR nomor BIB (scanner timing) =====================

    /**
     * PNG QR berisi nomor BIB PERSIS seperti di database (dibaca scanner /scanner, lalu
     * dinormalisasi App\Support\Bib). Hitam di atas putih, tanpa alpha (cepat di dompdf),
     * modul tajam tanpa anti-alias. Di-cache per nomor BIB.
     */
    public static function qr(string $bib): ?string
    {
        if ($bib === '' || ! preg_match('/^[0-9]+$/', $bib)) {
            return null;
        }

        $dir = storage_path('app/bib-cache');
        File::ensureDirectoryExists($dir);
        $file = $dir.'/qr-v1-'.$bib.'.png';
        if (! is_file($file)) {
            (new \chillerlan\QRCode\QRCode(new \chillerlan\QRCode\QROptions([
                'outputType' => \chillerlan\QRCode\Output\QROutputInterface::GDIMAGE_PNG,
                'eccLevel' => \chillerlan\QRCode\Common\EccLevel::M,
                'scale' => 24,              // px per modul → ±0,8 mm/modul pada 20 mm
                'addQuietzone' => true,
                'quietzoneSize' => 2,
                'outputBase64' => false,
                'imageTransparent' => false,
            ])))->render($bib, $file);
        }

        return $file;
    }

    // ===================== Panel elevasi (GD) =====================

    /** Path PNG panel elevasi (210 × PANEL_H mm, 300 dpi) untuk satu kategori. */
    public static function panel(GtrCategory $c, bool $flip = true): string
    {
        $ep = array_values(array_filter($c->elevation_profile ?? [], fn ($p) => is_array($p) && count($p) >= 2 && is_numeric($p[0]) && is_numeric($p[1])));
        $points = $c->coursePoints()->values()->all();
        $theme = self::theme($c);

        $dir = storage_path('app/bib-cache');
        File::ensureDirectoryExists($dir);
        $key = md5(json_encode([self::VERSION, $ep, $points, $theme, $flip, $c->distance, $c->tag, $c->start_time, $c->cut_off_time, $c->total_km, $c->elevation_gain, $c->gpx_file]));
        $file = $dir . "/panel-{$c->id}-{$key}.png";
        if (is_file($file)) {
            return $file;
        }

        $k = self::DPI / 25.4 * self::SUPERSAMPLE;         // piksel per mm (supersampling)
        $W = (int) round(self::W * $k);
        $H = (int) round(self::PANEL_H * $k);
        $im = imagecreatetruecolor($W, $H);
        imagealphablending($im, true);
        imageantialias($im, true);
        $col = fn (string $hex, int $alpha = 0) => imagecolorallocatealpha($im, ...[...self::rgb($hex), $alpha]);
        $px = fn (float $mm) => (int) round($mm * $k);
        $white = $col('#FFFFFF');
        $bg = $col($theme['panel']);
        $mix = fn (float $t) => $col(self::hex(array_map(fn ($a, $b) => $a + ($b - $a) * $t, self::rgb($theme['panel']), [255, 255, 255])));
        $chipBg = $mix(0.2);
        imagefilledrectangle($im, 0, 0, $W, $H, $bg);

        $font = fn (string $f) => resource_path('fonts/' . $f);
        $F900 = $font('Archivo-900.ttf');
        $F800 = $font('Archivo-800.ttf');
        $F600 = $font('PlusJakartaSans-600.ttf');
        $text = function (float $sizeMm, string $ff, float $xMm, float $yMm, int $color, string $s, string $align = 'left') use ($im, $k): float {
            $size = $sizeMm * $k * 0.75;
            $box = imagettfbbox($size, 0, $ff, $s);
            $w = $box[2] - $box[0];
            $x = $xMm * $k - ($align === 'right' ? $w : ($align === 'center' ? $w / 2 : 0));
            imagettftext($im, $size, 0, (int) round($x), (int) round($yMm * $k), $color, $ff, $s);

            return $w / $k;
        };
        $width = fn (float $sizeMm, string $ff, string $s) => (($b = imagettfbbox($sizeMm * $k * 0.75, 0, $ff, $s))[2] - $b[0]) / $k;
        $rrect = function (float $x, float $y, float $w, float $h, float $r, int $color) use ($im, $px) {
            [$x1, $y1, $x2, $y2, $rr] = [$px($x), $px($y), $px($x + $w), $px($y + $h), max(1, $px($r))];
            imagefilledrectangle($im, $x1 + $rr, $y1, $x2 - $rr, $y2, $color);
            imagefilledrectangle($im, $x1, $y1 + $rr, $x2, $y2 - $rr, $color);
            foreach ([[$x1 + $rr, $y1 + $rr], [$x2 - $rr, $y1 + $rr], [$x1 + $rr, $y2 - $rr], [$x2 - $rr, $y2 - $rr]] as [$cx, $cy]) {
                imagefilledellipse($im, $cx, $cy, $rr * 2, $rr * 2, $color);
            }
        };
        $line = function (float $x1, float $y1, float $x2, float $y2, float $tMm, int $color, ?array $dash = null) use ($im, $px, $k) {
            imagesetthickness($im, max(1, (int) round($tMm * $k)));
            if (! $dash) {
                imageline($im, $px($x1), $px($y1), $px($x2), $px($y2), $color);
            } else {
                $len = hypot($x2 - $x1, $y2 - $y1);
                for ($d = 0; $d < $len; $d += $dash[0] + $dash[1]) {
                    $e = min($len, $d + $dash[0]);
                    imageline($im, $px($x1 + ($x2 - $x1) * $d / $len), $px($y1 + ($y2 - $y1) * $d / $len), $px($x1 + ($x2 - $x1) * $e / $len), $px($y1 + ($y2 - $y1) * $e / $len), $color);
                }
            }
            imagesetthickness($im, 1);
        };

        // ---- Header: judul kiri, ringkasan kanan ----
        $fmtKm = fn (float $v) => rtrim(rtrim(number_format($v, 1, ',', '.'), '0'), ',');
        $hasEle = count($ep) >= 2;
        $total = (float) ($c->total_km ?: 0);
        if (! $total && $hasEle) {
            $total = (float) end($ep)[0];
        }
        if (! $total && preg_match('/(\d+(?:[.,]\d+)?)/', (string) ($c->distance ?: $c->name), $m)) {
            $total = (float) str_replace(',', '.', $m[1]);
        }
        $total = max($total, (float) (collect($points)->max('km') ?? 0), 0.1);

        $text(3.6, $F900, 11, 8.4, $white, 'PROFIL ELEVASI');
        $tw = $width(3.6, $F900, 'PROFIL ELEVASI');
        $text(2.6, $F800, 11 + $tw + 2.4, 8.4, $col('#FFFFFF', 45), mb_strtoupper(trim(($c->distance ?: $c->name) . ($c->tag ? ' · ' . $c->tag : ''))));

        $count = array_count_values(array_column($points, 'type'));
        $chips = array_values(array_filter([
            $c->start_time ? 'START ' . mb_strtoupper(trim($c->start_time)) : null,
            $fmtKm($total) . ' KM',
            ($gain = $c->gpxElevationGain() ?? $c->elevation_gain) ? 'GAIN ' . mb_strtoupper($gain) : null,
            implode(' · ', array_filter([
                ($count['ws'] ?? 0) ? $count['ws'] . ' WS' : null,
                ($count['cp'] ?? 0) ? $count['cp'] . ' CP' : null,
                ($count['cop'] ?? 0) ? $count['cop'] . ' COP' : null,
            ])) ?: null,
        ]));
        $cot = $c->cut_off_time && trim($c->cut_off_time) !== '-' ? 'COT ' . mb_strtoupper(trim($c->cut_off_time)) : null;
        $x = self::W - 11; // sisakan ruang lubang peniti di sudut
        if ($cot) {
            $w = $width(2.5, $F900, $cot) + 4.4;
            $rrect($x - $w, 3.8, $w, 6, 1.2, $white);
            $text(2.5, $F900, $x - $w / 2, 7.95, $bg, $cot, 'center');
            $x -= $w + 1.6;
        }
        foreach (array_reverse($chips) as $chip) {
            $w = $width(2.2, $F800, $chip) + 4;
            $rrect($x - $w, 3.8, $w, 6, 1.2, $chipBg);
            $text(2.2, $F800, $x - $w / 2, 7.8, $white, $chip, 'center');
            $x -= $w + 1.6;
        }

        // ---- Area grafik ----
        $X0 = 21;
        $X1 = self::W - 8;
        $Y0 = 22;
        $Y1 = self::PANEL_H - 8.5;
        $xAt = fn (float $km) => $X0 + min(max($km, 0), $total) / $total * ($X1 - $X0);

        if ($hasEle) {
            $eles = array_map('floatval', array_column($ep, 1));
            $lo = floor(min($eles) / 50) * 50;
            $hi = ceil(max($eles) / 50) * 50;
            if ($hi - $lo < 100) {
                $hi = $lo + 100;
            }
        } else {
            [$lo, $hi] = [0, 100];
        }
        $yAt = fn (float $e) => $Y1 - ($e - $lo) / ($hi - $lo) * ($Y1 - $Y0);
        $eleAt = function (float $km) use ($ep, $hasEle, $lo): float {
            if (! $hasEle) {
                return $lo + 30;
            }
            for ($i = 1; $i < count($ep); $i++) {
                if ($ep[$i][0] >= $km) {
                    $d = $ep[$i][0] - $ep[$i - 1][0];

                    return $d > 0 ? $ep[$i - 1][1] + ($ep[$i][1] - $ep[$i - 1][1]) * ($km - $ep[$i - 1][0]) / $d : $ep[$i][1];
                }
            }

            return (float) end($ep)[1];
        };

        // Grid & sumbu ketinggian (m) dan jarak (km)
        $grid = $col('#FFFFFF', 108);
        $axis = $col('#FFFFFF', 40);
        $step = ($hi - $lo) > 600 ? 200 : (($hi - $lo) > 300 ? 100 : 50);
        for ($e = $lo; $e <= $hi + 0.1; $e += $step) {
            $gy = $yAt($e);
            $line($X0, $gy, $X1, $gy, 0.15, $grid);
            if ($hasEle) {
                $text(2.1, $F800, $X0 - 1.6, $gy + 0.75, $axis, (int) $e . ' m', 'right');
            }
        }
        $kStep = $total > 25 ? 5 : ($total > 12 ? 2 : 1);
        $endW = $width(2.1, $F800, $fmtKm($total) . ' km');
        for ($km = 0; $km <= $total + 0.01; $km += $kStep) {
            $gx = $xAt($km);
            $line($gx, $Y1, $gx, $Y1 + 1, 0.2, $axis);
            if ($X1 - $gx > $endW + 3 || abs($km - $total) < 0.01) {
                $text(2.1, $F800, $gx, $Y1 + 4, $axis, $km . ' km', 'center');
            }
        }
        if (abs($total - round($total / $kStep) * $kStep) > 0.01) {
            $text(2.1, $F800, $X1, $Y1 + 4, $white, $fmtKm($total) . ' km', 'center');
        }

        // Profil: area transparan + garis putih
        $prof = $hasEle ? array_map(fn ($p) => [$xAt((float) $p[0]), $yAt((float) $p[1])], $ep) : [[$X0, $yAt($lo + 30)], [$X1, $yAt($lo + 30)]];
        $poly = [];
        foreach ($prof as [$a, $b]) {
            $poly[] = $px($a);
            $poly[] = $px($b);
        }
        array_push($poly, $px($X1), $px($Y1), $px($X0), $px($Y1));
        imagefilledpolygon($im, $poly, $col('#FFFFFF', 92));
        imagesetthickness($im, max(1, (int) round(0.55 * $k)));
        for ($i = 1; $i < count($prof); $i++) {
            imageline($im, $px($prof[$i - 1][0]), $px($prof[$i - 1][1]), $px($prof[$i][0]), $px($prof[$i][1]), $white);
        }
        imagesetthickness($im, 1);
        foreach ($prof as [$a, $b]) { // sambungan garis halus
            imagefilledellipse($im, $px($a), $px($b), (int) round(0.55 * $k), (int) round(0.55 * $k), $white);
        }

        // Start & finish
        foreach ([[0, 'START'], [$total, 'FINISH']] as [$km, $lbl]) {
            $sx = $xAt($km);
            $sy = $yAt($eleAt($km));
            imagefilledellipse($im, $px($sx), $px($sy), $px(2.4), $px(2.4), $white);
            imagefilledellipse($im, $px($sx), $px($sy), $px(1.2), $px(1.2), $bg);
            $lw = $width(1.9, $F900, $lbl) + 1.6;
            $lx = $km ? $sx - 1.4 - $lw : $sx + 1.4;
            $rrect($lx, $sy - 4.6, $lw, 2.9, 0.6, $bg);
            $text(1.9, $F900, $lx + 0.8, $sy - 2.45, $white, $lbl);
        }

        // Puncak
        if ($hasEle) {
            $top = array_reduce($ep, fn ($a, $p) => $a === null || $p[1] > $a[1] ? $p : $a);
            $tx = $xAt((float) $top[0]);
            $ty = $yAt((float) $top[1]);
            imagefilledpolygon($im, [$px($tx), $px($ty - 2.6), $px($tx - 1.3), $px($ty - 0.6), $px($tx + 1.3), $px($ty - 0.6)], $white);
            $sw = $width(2.2, $F900, (int) round($top[1]) . ' MDPL') + 1.6;
            $rrect($tx - $sw / 2, $ty - 6.4, $sw, 3.3, 0.6, $bg);
            $text(2.2, $F900, $tx, $ty - 3.85, $white, (int) round($top[1]) . ' MDPL', 'center');
        }

        // Titik pos: garis putus-putus, titik berwarna, label bertingkat di atas grafik
        $rows = [];
        usort($points, fn ($a, $b) => $a['km'] <=> $b['km']);
        foreach ($points as $p) {
            $pxm = $xAt((float) $p['km']);
            $pym = $yAt($eleAt((float) $p['km']));
            $tc = self::TYPE_COLOR[$p['type']] ?? '#FFFFFF';
            $label = $p['code'] . '  KM ' . $fmtKm((float) $p['km']);
            $w = $width(2.1, $F900, $label) + 3.4;
            $row = 0;
            while (collect($rows[$row] ?? [])->contains(fn ($r) => $pxm - $w / 2 < $r[1] && $pxm + $w / 2 > $r[0])) {
                $row++;
            }
            $row = min($row, 1);
            $rows[$row][] = [$pxm - $w / 2 - 0.8, $pxm + $w / 2 + 0.8];
            $ly = 12.2 + $row * 4.6;
            $lx = max(2, min(self::W - 2 - $w, $pxm - $w / 2));

            $line($pxm, $ly + 3.8, $pxm, $pym, 0.25, $col('#FFFFFF', 30), [0.9, 0.7]);
            $rrect($lx, $ly, $w, 3.8, 0.8, $col($tc));
            $text(2.1, $F900, $lx + $w / 2, $ly + 2.75, $p['type'] === 'cop' ? $bg : $white, $label, 'center');
            imagefilledellipse($im, $px($pxm), $px($pym), $px(2.2), $px(2.2), $white);
            imagefilledellipse($im, $px($pxm), $px($pym), $px(1.3), $px(1.3), $col($p['type'] === 'cop' ? $theme['panel'] : $tc));
            if ($p['cutoff'] ?? null) {
                $cw = $width(1.8, $F900, 'COT ' . $p['cutoff']) + 1.8;
                $rrect($pxm - $cw / 2, $pym + 1.6, $cw, 2.9, 0.6, $white);
                $text(1.8, $F900, $pxm, $pym + 3.75, $bg, 'COT ' . $p['cutoff'], 'center');
            }
        }

        if (! $hasEle) {
            $text(2.4, $F600, ($X0 + $X1) / 2, ($Y0 + $Y1) / 2, $col('#FFFFFF', 40), 'Profil elevasi tampil setelah file GPX kategori diunggah', 'center');
        }

        // Turunkan ke 300 dpi (anti-alias) lalu putar 180° bila diminta.
        $outW = (int) round($W / self::SUPERSAMPLE);
        $outH = (int) round($H / self::SUPERSAMPLE);
        $out = imagecreatetruecolor($outW, $outH);
        imagecopyresampled($out, $im, 0, 0, 0, 0, $outW, $outH, $W, $H);
        imagedestroy($im);
        if ($flip) {
            $rot = imagerotate($out, 180, 0);
            imagedestroy($out);
            $out = $rot;
        }
        imagepng($out, $file, 6);
        imagedestroy($out);

        return $file;
    }

    private static function bytes(string|false $v): int
    {
        $v = trim((string) $v);
        if ($v === '' || $v === '-1') {
            return -1;
        }
        $n = (int) $v;

        return match (strtolower(substr($v, -1))) { 'g' => $n * 1073741824, 'm' => $n * 1048576, 'k' => $n * 1024, default => $n };
    }
}
