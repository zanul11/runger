<?php

namespace App\Http\Controllers;

use App\Models\GtrCategory;
use Illuminate\View\View;

/**
 * Peta panitia /crew/peta — titik pasang sign arah & posisi marshal di atas rute GPX tiap kategori.
 * Halaman publik BACA-SAJA tanpa login (seperti /crew/roster & /crew/persiapan), noindex.
 * Data diatur di Filament → Kategori GTR → "Sign & Marshal (khusus panitia)".
 */
class CrewMapController extends Controller
{
    public function index(): View
    {
        $categories = GtrCategory::where('is_active', true)->orderBy('sort_order')->orderBy('id')->get()
            ->filter(fn (GtrCategory $c) => count($c->route_points ?? []) > 1)
            ->values();

        $courses = $categories->map(function (GtrCategory $c) {
            $ep = $c->elevation_profile ?? [];

            return [
                'id' => $c->id,
                'name' => $c->name,
                'distance' => $c->distance,
                'color' => $c->color ?: '#E2362B',
                'points' => $c->route_points,
                // KM sign/marshal mengikuti profil elevasi (sama dengan Course Map) → diskalakan ke garis rute
                'km_total' => count($ep) ? (float) end($ep)[0] : (float) $c->total_km,
                'signs' => $c->crewSigns()->all(),
                'marshals' => $c->crewMarshals()->all(),
                'stops' => $c->coursePoints()->map(fn ($p) => ['code' => $p['code'], 'type' => $p['type'], 'km' => $p['km'], 'title' => $p['title']])->all(),
            ];
        });

        // Default: kategori pertama yang sudah punya sign / marshal.
        $default = $courses->first(fn ($c) => $c['signs'] || $c['marshals'])['id'] ?? $courses->first()['id'] ?? null;

        return view('pages.crew-peta', [
            'courses' => $courses->all(),
            'defaultId' => $default,
            'signColors' => GtrCategory::SIGN_COLORS,
        ]);
    }
}
