<?php

namespace App\Http\Controllers;

use App\Models\GtrCategory;
use App\Models\GtrRegistration;
use App\Models\GtrSetting;
use App\Support\Bib;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * Kiosk "Cek BIB" (publik, tanpa login): layar + mesin QR scanner (mode keyboard/HID).
 * QR di BIB berisi nomor BIB → halaman menampilkan kartu BIB peserta (nomor + nama).
 *
 * Roster diunduh sekali ke browser lalu dicari lokal, supaya tampil instan & tetap jalan
 * walau sinyal di venue putus-putus. Isinya HANYA data yang sudah publik di Entry List
 * (BIB, nama, gender, kategori) — tanpa NIK, kontak, nomor registrasi, atau status racepack.
 */
class GtrBibCheckController extends Controller
{
    public const CACHE_SECONDS = 60;

    public function index(): View
    {
        return view('pages.gtr-bib-check', ['title' => GtrSetting::first()?->title ?: 'Gerung Trail Run 2026']);
    }

    /**
     * GET /gerung-trail-run/cek-bib/data
     * { categories: { "<id>": {name, tag, color, ele[]} }, runners: { "<bib>": {bib, name, gender, category_id} }, count, generated_at_ms }
     */
    public function data(): JsonResponse
    {
        $payload = Cache::remember('gtr:bib-check', self::CACHE_SECONDS, fn () => self::roster());

        return response()->json($payload)->header('Cache-Control', 'public, max-age='.self::CACHE_SECONDS);
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
            'categories' => (object) $categories->all(),
            'runners' => (object) $runners,
            'count' => count($runners),
            'generated_at_ms' => (int) floor(microtime(true) * 1000),
        ];
    }
}
