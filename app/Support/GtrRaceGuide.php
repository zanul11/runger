<?php

namespace App\Support;

use App\Models\GtrCategory;
use App\Models\GtrRule;
use App\Models\GtrSetting;
use App\Models\GtrSponsor;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Support\Facades\Storage;

/**
 * Menyusun data slide Race Guide GTR (format carousel Instagram 1080×1350)
 * dari konten admin: pengaturan, kategori, aturan lomba & sponsor.
 * Slide yang datanya kosong dilewati, dan dicatat di `missing` agar admin tahu apa yang perlu diisi.
 */
class GtrRaceGuide
{
    /** Ukuran kotak peta rute & profil elevasi di slide course (px). */
    private const MAP_W = 936;
    private const MAP_H = 470;
    private const ELE_W = 936;
    private const ELE_H = 214;

    public static function build(): array
    {
        $s = GtrSetting::current();
        $cats = GtrCategory::where('is_active', true)->orderBy('sort_order')->get();

        $categories = $cats->map(fn (GtrCategory $c) => self::category($c))->values();

        $rules = GtrRule::where('is_active', true)->orderBy('sort_order')->get()
            ->map(fn ($r) => ['title' => trim((string) $r->title), 'content' => trim((string) $r->content)])
            ->filter(fn ($r) => $r['title'] !== '' || $r['content'] !== '')
            ->values();

        $sponsors = GtrSponsor::where('is_active', true)->orderBy('sort_order')->orderBy('id')->get()
            ->map(fn ($sp) => ['name' => $sp->name, 'logo' => self::existing($sp->logo)])
            ->values();

        $racepackNotes = GtrSetting::lines($s->racepack_note);

        // Semua item perlengkapan wajib (gabungan, urutan kemunculan), dicek per kategori.
        $gear = [];
        foreach ($categories as $c) {
            foreach ($c['gear'] as $item) {
                $key = mb_strtolower(preg_replace('/\s+/', ' ', $item));
                $gear[$key] ??= ['item' => $item, 'cats' => []];
                $gear[$key]['cats'][] = $c['id'];
            }
        }

        $data = [
            'event' => [
                'title' => $s->title ?: 'Gerung Trail Run',
                'eyebrow' => $s->eyebrow,
                'date' => $s->date_text,
                'location' => $s->location_text,
                'categories' => $s->categories_text,
                'cover' => self::existing($s->header_image) ?? '/assets/gtr/gallery/ari.webp',
                'url' => route('gtr'),
                'qr' => self::qr(route('gtr')),
            ],
            'racepack' => [
                'location' => $s->racepack_location,
                'schedule' => $s->racepack_schedule,
                'notes' => $racepackNotes,
                'address' => $s->racepack_address,
                'maps' => $s->racepack_maps_url,
                'qr' => $s->racepack_maps_url ? self::qr($s->racepack_maps_url) : null,
                'directions' => GtrSetting::lines($s->racepack_directions),
            ],
            'rpc' => [
                'requirements' => GtrSetting::lines($s->rpc_requirements),
                'proxy' => GtrSetting::lines($s->rpc_proxy_note),
                'contact' => $s->rpc_contact,
            ],
            'venue' => [
                'name' => $s->venue_name,
                'address' => $s->venue_address,
                'maps' => $s->venue_maps_url,
                'qr' => $s->venue_maps_url ? self::qr($s->venue_maps_url) : null,
                'directions' => GtrSetting::lines($s->venue_directions),
                'facilities' => GtrSetting::lines($s->venue_facilities),
            ],
            'parking' => [
                'name' => $s->parking_name,
                'notes' => GtrSetting::lines($s->parking_notes),
            ],
            'assembly' => $s->assembly_time,
            'timeline' => self::timeline($categories->all(), $s),
            'categories' => $categories->all(),
            'gear' => array_values($gear),
            'rules' => $rules->all(),
            'sponsors' => $sponsors->all(),
            'supporters' => [
                ['name' => 'ALTI NTB', 'logo' => '/assets/gtr/alti.webp'],
                ['name' => 'Pemkab Lombok Barat', 'logo' => '/assets/gtr/lobar.webp'],
                ['name' => 'Disparekrafpora Lombok Barat', 'logo' => '/assets/gtr/dispar2.webp'],
                ['name' => 'PAM Giri Menang', 'logo' => '/assets/gtr/pam.webp'],
            ],
            'logos' => [
                'gtr' => '/assets/gtr/logo.webp',
                'runger' => '/assets/gtr/logo-white.webp',
            ],
        ];

        $data['missing'] = self::missing($data);

        return $data;
    }

    private static function category(GtrCategory $c): array
    {
        $points = $c->coursePoints()->values();
        $total = (float) ($c->total_km ?: 0);
        // Jarak resmi dari GPX; bila GPX belum diunggah, ambil angka dari teks jarak ("15 K" → 15).
        $kmLabel = $total ?: (preg_match('/(\d+(?:[.,]\d+)?)/', (string) ($c->distance ?: $c->name), $m) ? (float) str_replace(',', '.', $m[1]) : null);

        return [
            'id' => $c->id,
            'name' => $c->name,
            'distance' => $c->distance ?: $c->name,
            'tag' => $c->tag,
            'color' => $c->color ?: '#E53935',
            'start' => $c->start_time,
            'cot' => $c->cut_off_time && trim($c->cut_off_time) !== '-' ? $c->cut_off_time : null,
            'gain' => $c->gpxElevationGain() ?? $c->elevation_gain,
            'km' => $total ?: null,
            'km_label' => $kmLabel ?: null,
            'ws' => $points->where('type', 'ws')->count(),
            'cp' => $points->where('type', 'cp')->count(),
            'cop' => $points->where('type', 'cop')->count(),
            'points' => $points->map(fn ($p) => [
                'code' => $p['code'], 'type' => $p['type'], 'km' => $p['km'],
                'title' => $p['title'], 'cutoff' => $p['cutoff'], 'location' => $p['location'],
                'facilities' => $p['facilities'], 'notes' => $p['notes'],
            ])->all(),
            'pois' => $c->coursePois()->all(),
            'gear' => array_values(array_filter(array_map('trim', (array) ($c->mandatory_gear ?? [])))),
            'awards' => collect(preg_split('/\r\n|\r|\n/', (string) $c->award_prize))
                ->map(fn ($l) => trim(preg_replace('/^\s*[-•*]\s*/', '', $l)))->filter()->values()->all(),
            'rundown' => collect($c->rundown ?? [])
                ->filter(fn ($r) => is_array($r) && trim(($r['time'] ?? '') . ($r['activity'] ?? '')) !== '')
                ->map(fn ($r) => array_map(fn ($v) => trim((string) $v), $r + ['date' => null, 'time' => null, 'activity' => null, 'location' => null]))
                ->values()->all(),
            'map' => self::routeSvg($c->route_points ?? [], $points->all(), $total),
            'elevation' => self::elevationSvg($c->elevation_profile ?? [], $points->all()),
            'url' => route('gtr.category', $c->slug),
            'qr' => self::qr(route('gtr.category', $c->slug)),
            'gpx' => $c->canDownloadGpx(),
        ];
    }

    /**
     * Timeline event gabungan: rundown semua kategori digabung jadi satu (item yang sama
     * di beberapa kategori hanya muncul sekali), dikelompokkan per tanggal & diurutkan jam.
     * Jika rundown belum memuat hari lomba, hari lomba ditambahkan otomatis dari
     * jam kumpul, jam start & cut off tiap kategori.
     *
     * @return array<int, array{date: string, race: bool, items: array<int, array{time: ?string, activity: ?string, location: ?string, cats: array}>}>
     */
    private static function timeline(array $categories, GtrSetting $s): array
    {
        $allIds = array_column($categories, 'id');
        $days = [];
        $add = function (?string $date, ?string $time, ?string $activity, ?string $location, array $catIds) use (&$days) {
            [$dayKey, $sortDate] = self::dayKey($date);
            $timeNorm = self::timeNorm($time);
            $key = $timeNorm . '|' . mb_strtolower(preg_replace('/\s+/', ' ', trim((string) $activity))) . '|' . mb_strtolower(preg_replace('/\s+/', ' ', trim((string) $location)));
            $days[$dayKey] ??= ['date' => trim((string) $date), 'sort' => $sortDate, 'items' => []];
            $it = &$days[$dayKey]['items'][$key];
            $it ??= ['time' => $timeNorm ?: null, 'activity' => $activity ?: null, 'location' => $location ?: null, 'cats' => []];
            $it['cats'] = array_values(array_unique([...$it['cats'], ...$catIds]));
            // Pakai penulisan tanggal terlengkap (mis. yang memuat nama hari).
            if (mb_strlen(trim((string) $date)) > mb_strlen($days[$dayKey]['date'])) {
                $days[$dayKey]['date'] = trim((string) $date);
            }
        };

        foreach ($categories as $c) {
            foreach ($c['rundown'] as $r) {
                $add($r['date'], $r['time'], $r['activity'], $r['location'], [$c['id']]);
            }
        }

        // Hari lomba otomatis bila belum ada di rundown.
        [$raceKey] = self::dayKey($s->date_text);
        if ($s->date_text && ! isset($days[$raceKey])) {
            if ($s->assembly_time) {
                $add($s->date_text, $s->assembly_time, 'Runner Assembly', $s->location_text, $allIds);
            }
            foreach ($categories as $c) {
                if ($c['start']) {
                    $add($s->date_text, $c['start'], 'Flag Off', $s->location_text, [$c['id']]);
                }
            }
            foreach ($categories as $c) {
                if ($cot = self::cutOffClock($c['start'], $c['cot'])) {
                    $add($s->date_text, $cot, 'Cut Off Time', 'Finish', [$c['id']]);
                }
            }
        }

        uasort($days, fn ($a, $b) => [$a['sort'] === null, $a['sort']] <=> [$b['sort'] === null, $b['sort']]);

        return collect($days)->map(function ($d, $k) use ($allIds, $raceKey) {
            $items = array_values($d['items']);
            // Urut jam mulai; item tanpa jam di akhir, urutan asli dipertahankan.
            $items = collect($items)->map(fn ($it, $i) => $it + ['_o' => $i])
                ->sortBy(fn ($it) => [self::timeStart($it['time']) ?? 9999, $it['_o']])
                ->map(function ($it) use ($allIds) {
                    unset($it['_o']);
                    // Item yang berlaku untuk semua kategori tidak perlu label kategori.
                    $it['cats'] = count(array_diff($allIds, $it['cats'])) === 0 ? [] : $it['cats'];

                    return $it;
                })->values()->all();

            // Tanggal ditulis seragam lengkap dengan nama hari bila bisa dibaca.
            $date = $d['sort'] ? \Carbon\Carbon::parse($d['sort'])->locale('id')->translatedFormat('l, j F Y') : $d['date'];

            return ['date' => $date, 'race' => $k === $raceKey, 'items' => $items];
        })->values()->all();
    }

    private const MONTHS_ID = [
        'januari' => 'january', 'februari' => 'february', 'maret' => 'march', 'mei' => 'may',
        'juni' => 'june', 'juli' => 'july', 'agustus' => 'august', 'oktober' => 'october', 'desember' => 'december',
    ];

    /** Kunci pengelompokan tanggal (Y-m-d bila bisa dibaca) + nilai urut. */
    private static function dayKey(?string $date): array
    {
        $raw = mb_strtolower(trim((string) $date));
        if ($raw === '') {
            return ['', null];
        }
        $txt = preg_replace('/^[^\d,]+,\s*/u', '', $raw); // buang nama hari: "minggu, 29 ..." → "29 ..."
        $txt = strtr($txt, self::MONTHS_ID);
        try {
            $d = \Carbon\Carbon::parse($txt);

            return [$d->format('Y-m-d'), $d->format('Y-m-d')];
        } catch (\Throwable) {
            return [preg_replace('/\s+/', ' ', $raw), null];
        }
    }

    /** "15:30 - 18:00 WITA" → "15:30 – 18:00" (zona waktu ditulis sekali di slide). */
    private static function timeNorm(?string $time): string
    {
        $t = preg_replace('/\b(WITA|WIB|WIT)\b\.?/i', '', (string) $time);
        $t = preg_replace('/\s*[-–—]\s*/u', ' – ', $t);
        $t = preg_replace('/(\d{1,2})\.(\d{2})/', '$1:$2', $t);

        return trim(preg_replace('/\s+/', ' ', $t));
    }

    private static function timeStart(?string $time): ?int
    {
        return preg_match('/(\d{1,2}):(\d{2})/', (string) $time, $m) ? (int) $m[1] * 60 + (int) $m[2] : null;
    }

    /** Jam cut off: COT berupa jam ("10:00") dipakai langsung, berupa durasi ("4 Jam") dihitung dari jam start. */
    private static function cutOffClock(?string $start, ?string $cot): ?string
    {
        $cot = trim((string) $cot);
        if ($cot === '') {
            return null;
        }
        if (preg_match('/^(\d{1,2})[:.](\d{2})/', $cot, $m)) {
            return sprintf('%02d:%s', $m[1], $m[2]);
        }
        $begin = self::timeStart(self::timeNorm($start));
        if ($begin !== null && preg_match('/^(\d+(?:[.,]\d+)?)\s*(jam|hours?|hrs?|h)\b/i', $cot, $m)) {
            $end = $begin + (int) round((float) str_replace(',', '.', $m[1]) * 60);

            return sprintf('%02d:%02d', intdiv($end, 60) % 24, $end % 60);
        }

        return null;
    }

    /** Daftar konten admin yang masih kosong sehingga slide-nya tidak dibuat. */
    private static function missing(array $d): array
    {
        $m = [];
        if (! $d['racepack']['location'] && ! $d['racepack']['schedule'] && ! $d['racepack']['notes']) {
            $m[] = 'Race Pack Collection: isi lokasi, jadwal & catatan race pack di Header GTR → Race Pack & Race Day.';
        }
        if ($d['racepack']['location'] && ! $d['racepack']['maps']) {
            $m[] = 'Race Pack Collection: isi alamat, link Google Maps (untuk QR) & petunjuk arah lokasi RPC di Header GTR → bagian Race Guide (opsional).';
        }
        if (! $d['rpc']['requirements'] && ! $d['rpc']['proxy']) {
            $m[] = 'RPC Requirements: isi syarat pengambilan race pack di Header GTR → bagian Race Guide.';
        }
        if (! $d['venue']['name'] && ! $d['venue']['directions']) {
            $m[] = 'Race Village: isi nama venue, link maps & petunjuk arah di Header GTR → bagian Race Guide.';
        }
        if (! $d['parking']['name'] && ! $d['parking']['notes']) {
            $m[] = 'Parking: lokasi & catatan parkir belum diisi (opsional).';
        }
        if (! $d['gear']) {
            $m[] = 'Mandatory Gear: isi perlengkapan wajib di tiap kategori.';
        }
        if (! $d['rules']) {
            $m[] = 'Race Rules: belum ada aturan lomba aktif di menu Rules GTR.';
        }
        if (! collect($d['categories'])->contains(fn ($c) => $c['pois'])) {
            $m[] = 'Course Info: belum ada info / bahaya jalur di kategori (opsional).';
        }
        if (! $d['timeline']) {
            $m[] = 'Event Timeline: isi rundown di kategori atau tanggal event di Header GTR (opsional).';
        }
        if (! $d['assembly']) {
            $m[] = 'Race Day: jam kumpul belum diisi di Header GTR (opsional).';
        }

        return $m;
    }

    /** URL publik file di disk public, atau null bila kosong / filenya tidak ada. */
    private static function existing(?string $path): ?string
    {
        if (! $path) {
            return null;
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        // Path relatif (tanpa host) agar gambar selalu satu origin dengan halaman — syarat export PNG.
        return Storage::disk('public')->exists($path)
            ? '/' . ltrim((string) parse_url(Storage::disk('public')->url($path), PHP_URL_PATH), '/')
            : null;
    }

    private static function qr(string $url): string
    {
        $opt = new QROptions([
            'outputType' => QROutputInterface::MARKUP_SVG,
            'outputBase64' => true,
            'addQuietzone' => false,
            'eccLevel' => \chillerlan\QRCode\Common\EccLevel::M,
        ]);

        return (new QRCode($opt))->render($url);
    }

    /**
     * Rute GPX → path SVG (proyeksi equirectangular, dipaskan ke kotak peta),
     * plus posisi titik WS/CP/COP, start & finish.
     */
    private static function routeSvg(array $route, array $points, float $totalKm): ?array
    {
        $route = array_values(array_filter($route, fn ($p) => is_array($p) && count($p) >= 2 && is_numeric($p[0]) && is_numeric($p[1])));
        if (count($route) < 2) {
            return null;
        }

        $lats = array_column($route, 0);
        $lngs = array_column($route, 1);
        $k = cos(deg2rad((min($lats) + max($lats)) / 2));
        $xs = array_map(fn ($lng) => $lng * $k, $lngs);
        $ys = array_map(fn ($lat) => -$lat, $lats);

        $pad = 44;
        $spanX = max(max($xs) - min($xs), 1e-9);
        $spanY = max(max($ys) - min($ys), 1e-9);
        $scale = min((self::MAP_W - 2 * $pad) / $spanX, (self::MAP_H - 2 * $pad) / $spanY);
        $offX = (self::MAP_W - $spanX * $scale) / 2;
        $offY = (self::MAP_H - $spanY * $scale) / 2;

        $xy = [];
        foreach ($route as $i => $_) {
            $xy[] = [round($offX + ($xs[$i] - min($xs)) * $scale, 1), round($offY + ($ys[$i] - min($ys)) * $scale, 1)];
        }

        // Jarak kumulatif (km) di sepanjang rute untuk menaruh titik berdasarkan KM.
        $cum = [0.0];
        for ($i = 1; $i < count($route); $i++) {
            $cum[] = $cum[$i - 1] + self::haversine($route[$i - 1], $route[$i]);
        }
        $len = end($cum) ?: 1;
        $ratio = $totalKm > 0 ? $len / $totalKm : 1;
        $at = function (float $km) use ($cum, $xy, $ratio) {
            $target = $km * $ratio;
            foreach ($cum as $i => $d) {
                if ($d >= $target) {
                    return $xy[$i];
                }
            }

            return end($xy);
        };

        $path = 'M' . implode(' L', array_map(fn ($p) => $p[0] . ' ' . $p[1], $xy));
        $first = $xy[0];
        $last = end($xy);
        $loop = hypot($first[0] - $last[0], $first[1] - $last[1]) < 24;

        return [
            'w' => self::MAP_W,
            'h' => self::MAP_H,
            'path' => $path,
            'start' => $first,
            'finish' => $last,
            'loop' => $loop,
            'points' => self::placeLabels(array_map(fn ($p) => ['code' => $p['code'], 'type' => $p['type'], 'xy' => $at((float) $p['km'])], $points)),
        ];
    }

    /**
     * Posisi label kode pos (relatif ke titik) yang tidak saling tumpuk & tetap di dalam peta.
     * Hasil: `label` = [x, y, lebar].
     */
    private static function placeLabels(array $points): array
    {
        $placed = [];
        $h = 30;
        foreach ($points as &$p) {
            $w = 14 + strlen($p['code']) * 13;
            [$px, $py] = $p['xy'];
            $cands = [[16, -38], [16, 8], [-16 - $w, -38], [-16 - $w, 8], [16, -76], [-16 - $w, -76], [16, 46], [-16 - $w, 46]];
            $choice = $cands[0];
            foreach ($cands as $c) {
                $box = [$px + $c[0], $py + $c[1], $w, $h];
                $inside = $box[0] >= 6 && $box[1] >= 6 && $box[0] + $w <= self::MAP_W - 6 && $box[1] + $h <= self::MAP_H - 6;
                $free = collect($placed)->every(fn ($b) => $box[0] + $w + 4 <= $b[0] || $b[0] + $b[2] + 4 <= $box[0] || $box[1] + $h + 4 <= $b[1] || $b[1] + $h + 4 <= $box[1]);
                if ($inside && $free) {
                    $choice = $c;
                    break;
                }
            }
            $placed[] = [$px + $choice[0], $py + $choice[1], $w];
            $p['label'] = [$choice[0], $choice[1], $w];
        }

        return $points;
    }

    /** Profil elevasi → path area & garis SVG, plus posisi X titik WS/CP. */
    private static function elevationSvg(array $ep, array $points): ?array
    {
        $ep = array_values(array_filter($ep, fn ($p) => is_array($p) && count($p) >= 2 && is_numeric($p[0]) && is_numeric($p[1])));
        if (count($ep) < 2) {
            return null;
        }

        $kms = array_map('floatval', array_column($ep, 0));
        $eles = array_map('floatval', array_column($ep, 1));
        $maxKm = max(end($kms), 0.001);
        $min = min($eles);
        $max = max($eles);
        $span = max($max - $min, 1);
        // Pita atas untuk label kode pos (2 baris pill), profil di bawahnya.
        $pillH = 28;
        $rowsY = [4, 4 + $pillH + 6];
        $top = 4 + 2 * ($pillH + 6) + 10;
        $h = self::ELE_H - $top - 4;

        $x = fn (float $km) => round($km / $maxKm * self::ELE_W, 1);
        $y = fn (float $e) => round($top + $h - ($e - $min) / $span * $h, 1);
        $eleAt = function (float $km) use ($kms, $eles): float {
            for ($i = 1; $i < count($kms); $i++) {
                if ($kms[$i] >= $km) {
                    $d = $kms[$i] - $kms[$i - 1];

                    return $d > 0 ? $eles[$i - 1] + ($eles[$i] - $eles[$i - 1]) * ($km - $kms[$i - 1]) / $d : $eles[$i];
                }
            }

            return end($eles);
        };

        $line = [];
        foreach ($kms as $i => $km) {
            $line[] = $x($km) . ' ' . $y($eles[$i]);
        }

        // Label pill di atas garis putus-putus; bila berdekatan, pindah ke baris kedua.
        $rowEnd = [-INF, -INF];
        $marks = [];
        foreach ($points as $p) {
            $km = min((float) $p['km'], $maxKm);
            $px = $x($km);
            $w = 18 + strlen($p['code']) * 11;
            $lx = max(0, min(self::ELE_W - $w, $px - $w / 2));
            $row = $lx >= $rowEnd[0] + 6 ? 0 : ($lx >= $rowEnd[1] + 6 ? 1 : ($rowEnd[0] <= $rowEnd[1] ? 0 : 1));
            $rowEnd[$row] = $lx + $w;
            $marks[] = [
                'code' => $p['code'], 'type' => $p['type'],
                'x' => $px, 'y' => $y($eleAt($km)),
                'lx' => round($lx, 1), 'ly' => $rowsY[$row], 'lw' => $w, 'lh' => $pillH,
            ];
        }

        return [
            'w' => self::ELE_W,
            'h' => self::ELE_H,
            'top' => $top,
            'line' => 'M' . implode(' L', $line),
            'area' => 'M0 ' . self::ELE_H . ' L' . implode(' L', $line) . ' L' . self::ELE_W . ' ' . self::ELE_H . ' Z',
            'min' => (int) round($min),
            'max' => (int) round($max),
            'km' => round($maxKm, 1),
            'points' => $marks,
        ];
    }

    private static function haversine(array $a, array $b): float
    {
        $r = 6371.0;
        $dLat = deg2rad($b[0] - $a[0]);
        $dLng = deg2rad($b[1] - $a[1]);
        $h = sin($dLat / 2) ** 2 + cos(deg2rad($a[0])) * cos(deg2rad($b[0])) * sin($dLng / 2) ** 2;

        return 2 * $r * asin(min(1, sqrt($h)));
    }
}
