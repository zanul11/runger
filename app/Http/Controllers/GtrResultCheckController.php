<?php

namespace App\Http\Controllers;

use App\Models\GtrCategory;
use App\Models\GtrResult;
use App\Models\GtrSetting;
use App\Support\Bib;

/**
 * Kiosk "Cek Hasil" (publik, tanpa login): sama dengan Cek BIB — layar + mesin QR scanner yang
 * membaca QR di BIB — ditambah hasil lomba peserta (status, waktu resmi, net time, pace, peringkat).
 *
 * Mesin kiosk (scanner HID, suara, overlay fokus, offline/service worker) dipakai bersama
 * GtrBibCheckController; view yang sama dengan mode 'result'.
 *
 * Isi hasil = data yang memang publik di halaman Results / Live Tracking:
 *  - results_published = true → hasil resmi: FINISHER (waktu + peringkat), DNF, DQ.
 *  - belum dipublikasikan     → HASIL SEMENTARA: hanya FINISHER. Status lain disembunyikan, karena
 *    selama lomba pelari yang masih di lintasan terhitung DNF dan DQ masih menunggu review admin.
 *  - DNS / belum ada hasil    → result = null ("Hasil belum tersedia").
 */
class GtrResultCheckController extends GtrBibCheckController
{
    /** Lebih pendek dari Cek BIB: hasil berubah terus selama pelari masuk finish. */
    public const CACHE_SECONDS = 20;

    public const SCOPE = '/gerung-trail-run/cek-hasil';

    public const MODE = 'result';

    public const ROUTE = 'gtr.result-check';

    public const CACHE_KEY = 'gtr:result-check:v1';

    public const SW_CACHE_PREFIX = 'gtr-resultcheck-';

    /**
     * Roster Cek BIB + `published` dan `runners.<bib>.result`:
     * { status, gun, net, pace, rank, rank_total, rank_gender, gender_total } atau null.
     */
    public static function roster(): array
    {
        $payload = parent::roster();
        $published = (bool) GtrSetting::first()?->results_published;
        $km = GtrCategory::pluck('total_km', 'id')->map(fn ($v) => (float) $v);
        $statuses = $published
            ? [GtrResult::STATUS_FINISHER, GtrResult::STATUS_DNF, GtrResult::STATUS_DQ]
            : [GtrResult::STATUS_FINISHER];

        $results = GtrResult::query()->whereIn('status', $statuses)
            ->with('registration:id,gtr_category_id,bib_number,gender')
            ->get(['id', 'gtr_registration_id', 'status', 'gun_time_ms', 'net_time_ms', 'rank_overall', 'rank_gender'])
            ->filter(fn (GtrResult $r) => $r->registration && isset($payload['runners'][Bib::normalize($r->registration->bib_number)]));

        // Pembagi peringkat: jumlah finisher berperingkat per kategori & per kategori+gender.
        $ranked = $results->filter(fn (GtrResult $r) => $r->status === GtrResult::STATUS_FINISHER && $r->rank_overall !== null);
        $catTotal = $ranked->countBy(fn (GtrResult $r) => $r->registration->gtr_category_id);
        $genderTotal = $ranked->filter(fn (GtrResult $r) => $r->rank_gender !== null)
            ->countBy(fn (GtrResult $r) => $r->registration->gtr_category_id.'-'.$r->registration->genderCode());

        foreach ($payload['runners'] as &$runner) {
            $runner['result'] = null;
        }
        unset($runner);

        foreach ($results as $r) {
            $reg = $r->registration;
            $bib = Bib::normalize($reg->bib_number);
            $finisher = $r->status === GtrResult::STATUS_FINISHER && $r->gun_time_ms !== null;
            $runMs = $r->net_time_ms ?? $r->gun_time_ms;
            $catKm = $km[$reg->gtr_category_id] ?? 0;

            $payload['runners'][$bib]['result'] = [
                'status' => $r->status,
                'gun' => $finisher ? GtrResult::formatMs($r->gun_time_ms, false) : null,
                'net' => $finisher ? GtrResult::formatMs($r->net_time_ms, false) : null,
                'pace' => $finisher && $catKm > 0 ? self::pace($runMs / 1000 / $catKm) : null,
                'rank' => $finisher ? $r->rank_overall : null,
                'rank_total' => $finisher ? ($catTotal[$reg->gtr_category_id] ?? 0) : null,
                'rank_gender' => $finisher ? $r->rank_gender : null,
                'gender_total' => $finisher ? ($genderTotal[$reg->gtr_category_id.'-'.$reg->genderCode()] ?? 0) : null,
            ];
        }

        $payload['published'] = $published;
        $payload['results'] = $results->count();

        return $payload;
    }

    /** Detik per km → "m:ss". */
    private static function pace(float $secPerKm): string
    {
        $s = (int) round($secPerKm);

        return intdiv($s, 60).':'.str_pad((string) ($s % 60), 2, '0', STR_PAD_LEFT);
    }
}
