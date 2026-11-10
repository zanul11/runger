<?php

namespace App\Services;

use App\Casts\DateTimeMs;
use App\Models\GtrCategory;
use App\Models\GtrRegistration;
use App\Models\GtrScanLog;
use App\Models\User;
use App\Support\Bib;
use App\Support\Passes;
use Illuminate\Support\Carbon;

/**
 * Data sesi scanner: pos marshal, kategori yang melewati pos, roster peserta ringkas untuk
 * validasi OFFLINE di Dexie, dan jam server untuk menghitung clock offset.
 *
 * Roster berisi SEMUA peserta race-eligible (lunas + punya BIB, ±300 baris), bukan hanya
 * kategori pos ini, supaya scanner bisa membedakan "salah kategori/pos" dari "BIB tidak terdaftar".
 */
class GtrTimingSession
{
    public function payload(User $user): array
    {
        $assignment = $user->activeAssignment();
        $point = $assignment?->timingPoint;
        $pointCategories = $point ? $point->categories()->get()->keyBy('id') : collect();

        $categories = GtrCategory::orderBy('sort_order')->orderBy('id')->get()
            ->map(function (GtrCategory $c) use ($pointCategories, $point) {
                $pivot = $pointCategories->get($c->id)?->pivot;

                return [
                    'id' => $c->id,
                    'name' => $c->name,
                    'passes_point' => $pivot !== null,
                    'sequence' => $pivot?->sequence,
                    'is_mandatory' => $pivot ? (bool) $pivot->is_mandatory : null,
                    'cutoff_at_ms' => $pivot?->cutoff_at ? DateTimeMs::toMs(Carbon::parse($pivot->cutoff_at)) : null,
                    // Berapa kali kategori ini melewati pos marshal (15K di Pertigaan: WS1 & WS2).
                    'visits' => $pivot ? array_map(fn ($v) => ['code' => $v['code'], 'km' => $v['km']], Passes::visits($pivot->visits, $point->code, $pivot->km)) : [],
                    'gun_time_ms' => DateTimeMs::toMs($c->officialGunTime()),
                    'gun_fired' => $c->gun_fired_at !== null,
                ];
            })->values();

        $registrations = GtrRegistration::query()->raceEligible()
            ->with('runner:id,first_name,last_name')
            ->orderBy('bib_number')
            ->get(['id', 'runner_id', 'gtr_category_id', 'nomor_registrasi', 'bib_number', 'full_name', 'gender', 'size', 'nik', 'racepack_collected_at']);

        // NIK hanya untuk verifikasi identitas di meja racepack — tidak dikirim ke HP marshal timing.
        $withNik = in_array($user->role, [User::ROLE_RACEPACK, User::ROLE_ADMIN], true);

        // Lewat yang sudah tercatat di pos ini (dari device mana pun) → scanner tahu "sudah discan"
        // atau "lewat ke-2". Scan dikelompokkan per lewat (jarak ≥ 10 menit, App\Support\Passes).
        $seen = $point
            ? GtrScanLog::valid()->where('gtr_timing_point_id', $point->id)
                ->get(['gtr_registration_id', 'scanned_at'])
                ->groupBy('gtr_registration_id')
                ->map(fn ($g) => Passes::cluster($g->map(fn ($s) => DateTimeMs::toMs($s->scanned_at))->all()))
            : collect();

        $participants = $registrations->map(fn (GtrRegistration $r) => array_merge([
            'bib' => Bib::normalize($r->bib_number) ?? (string) $r->bib_number,
            'registration_number' => $r->nomor_registrasi,
            'name' => $r->displayName(),
            'category_id' => $r->gtr_category_id,
            'gender' => $r->genderCode(),
            'size' => $r->size,
            'racepack' => $r->racepack_collected_at !== null,
            'seen_at_ms' => $seen[$r->id][0] ?? null,
            'seen_passes_ms' => $seen[$r->id] ?? [],
        ], $withNik ? ['nik' => $r->nik] : []))->values();

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'role' => $user->role,
            ],
            'timing_point' => $point ? [
                'id' => $point->id,
                'code' => $point->code,
                'name' => $point->name,
                'type' => $point->type,
            ] : null,
            'categories' => $categories,
            'participants' => $participants,
            'rules' => [
                'dedup_window_ms' => GtrScanLog::DEDUP_WINDOW_MS,
                'repeat_pass_gap_ms' => Passes::GAP_MS,
                'bib_max_length' => Bib::MAX_LENGTH,
            ],
            'server_time_ms' => DateTimeMs::toMs(now()),
        ];
    }
}
