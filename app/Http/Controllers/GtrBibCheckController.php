<?php

namespace App\Http\Controllers;

use App\Models\GtrCategory;
use App\Models\GtrRegistration;
use App\Models\GtrSetting;
use App\Support\Bib;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * Kiosk "Cek BIB" (publik, tanpa login): layar + mesin QR scanner (mode keyboard/HID).
 * QR di BIB berisi nomor BIB → halaman menampilkan kartu BIB peserta (nomor + nama).
 *
 * Roster diunduh sekali ke browser lalu dicari lokal, supaya tampil instan & tetap jalan
 * walau sinyal di venue putus-putus. Isinya HANYA data yang sudah publik di Entry List
 * (BIB, nama, gender, kategori) — tanpa NIK, kontak, nomor registrasi, atau status racepack.
 *
 * Offline penuh: font di-host sendiri + service worker (sw()) menyimpan halaman, data, font, dan
 * logo — sekali dibuka saat online, kiosk tetap hidup walau internet mati & browser di-reload.
 */
class GtrBibCheckController extends Controller
{
    public const CACHE_SECONDS = 60;

    /** Path halaman (= scope service worker, tanpa slash akhir — .htaccess membuang slash akhir). */
    public const SCOPE = '/gerung-trail-run/cek-bib';

    /** Mode kiosk di view: 'bib' (kartu BIB) / 'result' (kartu BIB + hasil lomba, GtrResultCheckController). */
    public const MODE = 'bib';

    /** Prefix nama route: <ROUTE>, <ROUTE>.data, <ROUTE>.sw. */
    public const ROUTE = 'gtr.bib-check';

    public const CACHE_KEY = 'gtr:bib-check:v2';

    /** Prefix nama cache service worker (harus unik per kiosk — activate() membuang prefix yang sama). */
    public const SW_CACHE_PREFIX = 'gtr-bibcheck-';

    /** Logo yang dipakai halaman (di-precache service worker). */
    public const IMAGES = [
        'assets/gtr/logo.jpeg',
        'assets/gtr/bib-check/gtr-logo.webp',
        'assets/runger-logo.png',
        'assets/gtr/keteri.webp',
        'assets/gtr/bib-check/sponsor-alti.webp',
        'assets/gtr/bib-check/sponsor-lobar.webp',
        'assets/gtr/bib-check/sponsor-dispar.webp',
        'assets/gtr/bib-check/bank-ntb-syariah.webp',
        'assets/gtr/bib-check/pam.webp',
    ];

    /** Font Barlow lokal (subset Google Fonts, OFL): [family, style, weight, file]. Hanya bobot yang dipakai. */
    public const FONTS = [
        ['Barlow', 'normal', 400, 'barlow-400'],
        ['Barlow', 'normal', 500, 'barlow-500'],
        ['Barlow Condensed', 'normal', 500, 'barlowc-500'],
        ['Barlow Condensed', 'normal', 700, 'barlowc-700'],
        ['Barlow Condensed', 'normal', 800, 'barlowc-800'],
        ['Barlow Condensed', 'italic', 900, 'barlowc-900i'],
    ];

    public const FONT_DIR = 'assets/gtr/bib-check/fonts';

    /** unicode-range per subset (sama dengan CSS Google Fonts). */
    public const SUBSETS = [
        'latin-ext' => 'U+0100-02BA, U+02BD-02C5, U+02C7-02CC, U+02CE-02D7, U+02DD-02FF, U+0304, U+0308, U+0329, U+1D00-1DBF, U+1E00-1E9F, U+1EF2-1EFF, U+2020, U+20A0-20AB, U+20AD-20C4, U+2113, U+2C60-2C7F, U+A720-A7FF',
        'latin' => 'U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+0329, U+2000-206F, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD',
    ];

    public function index(): View
    {
        return view('pages.gtr-bib-check', [
            'title' => GtrSetting::first()?->title ?: 'Gerung Trail Run 2026',
            'fontFaces' => self::fontFaces(),
            'mode' => static::MODE,
            'dataUrl' => route(static::ROUTE.'.data'),
            'swUrl' => route(static::ROUTE.'.sw'),
            'scope' => static::SCOPE,
        ]);
    }

    /**
     * GET /gerung-trail-run/cek-bib/data
     * { categories: { "<id>": {name, tag, color, ele[]} }, runners: { "<bib>": {bib, name, gender, category_id} }, count, generated_at_ms }
     */
    public function data(): JsonResponse
    {
        // Cache HANYA array biasa: config cache.serializable_classes=false menolak unserialize
        // stdClass (hasilnya __PHP_Incomplete_Class → roster kosong). Objek dibuat setelah dibaca.
        $payload = Cache::remember(static::CACHE_KEY, static::CACHE_SECONDS, fn () => static::roster());
        // Map → objek JSON (bukan [] saat kosong) supaya browser selalu menerima {"<bib>": …}.
        $payload['categories'] = (object) $payload['categories'];
        $payload['runners'] = (object) $payload['runners'];

        return response()->json($payload)->header('Cache-Control', 'public, max-age='.static::CACHE_SECONDS);
    }

    /** GET /gerung-trail-run/cek-bib/sw.js — service worker kiosk (scope = halaman kiosk). */
    public function sw(): Response
    {
        $js = view('pages.gtr-bib-check-sw', [
            'version' => static::version(),
            'cachePrefix' => static::SW_CACHE_PREFIX,
            'page' => route(static::ROUTE),
            'data' => route(static::ROUTE.'.data'),
            'precache' => static::precacheUrls(),
        ])->render();

        return response($js, 200, [
            'Content-Type' => 'application/javascript; charset=utf-8',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Service-Worker-Allowed' => static::SCOPE,
        ]);
    }

    /** @return list<array{family:string, style:string, weight:int, url:string, range:string}> */
    public static function fontFaces(): array
    {
        $faces = [];
        foreach (self::FONTS as [$family, $style, $weight, $file]) {
            foreach (self::SUBSETS as $subset => $range) {
                $faces[] = [
                    'family' => $family, 'style' => $style, 'weight' => $weight, 'range' => $range,
                    'url' => asset(self::FONT_DIR."/{$file}-{$subset}.woff2"),
                ];
            }
        }

        return $faces;
    }

    /** @return list<string> */
    public static function precacheUrls(): array
    {
        return [
            route(static::ROUTE),
            ...array_map(fn ($p) => asset($p), self::IMAGES),
            ...array_column(self::fontFaces(), 'url'),
        ];
    }

    /** Berubah setiap halaman / SW / aset diubah → cache lama dibuang, kiosk ambil versi baru. */
    public static function version(): string
    {
        $files = [
            resource_path('views/pages/gtr-bib-check.blade.php'),
            resource_path('views/pages/gtr-bib-check-sw.blade.php'),
            ...array_map(fn ($p) => public_path($p), self::IMAGES),
        ];

        return substr(md5(implode('|', array_map(fn ($f) => is_file($f) ? $f.':'.filemtime($f) : $f, $files))), 0, 12);
    }

    public static function roster(): array
    {
        $categories = GtrCategory::orderBy('sort_order')->orderBy('id')
            ->get(['id', 'name', 'tag', 'color', 'elevation_profile'])
            ->mapWithKeys(fn (GtrCategory $c) => [$c->id => [
                'name' => $c->name,
                'tag' => $c->tag,
                'color' => $c->color ?: '#E53935',
                // Hanya ketinggian (m) — untuk siluet punggungan bukit di latar kartu BIB.
                'ele' => array_values(array_map(fn ($p) => (int) round((float) ($p[1] ?? 0)), $c->elevation_profile ?? [])),
            ]]);

        $runners = [];
        GtrRegistration::query()->raceEligible()
            ->with('runner:id,first_name,last_name')
            ->get(['id', 'runner_id', 'gtr_category_id', 'bib_number', 'full_name', 'gender'])
            ->each(function (GtrRegistration $r) use (&$runners) {
                $bib = Bib::normalize($r->bib_number);
                if ($bib !== null) {
                    $runners[$bib] = ['bib' => $bib, 'name' => $r->displayName(), 'gender' => $r->genderCode(), 'category_id' => $r->gtr_category_id];
                }
            });

        return [
            'categories' => $categories->all(),
            'runners' => $runners,
            'count' => count($runners),
            'generated_at_ms' => (int) floor(microtime(true) * 1000),
        ];
    }
}
