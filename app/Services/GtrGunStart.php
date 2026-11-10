<?php

namespace App\Services;

use App\Models\GtrCategory;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Tembak start (gun) satu kategori: simpan waktu AKTUAL (ms) + siapa yang menembak.
 * Dipakai API scanner dan (Fase 4) tombol Start di Filament.
 */
class GtrGunStart
{
    /**
     * @return array{fired: bool, category: GtrCategory} fired=false bila sudah ditembak & tidak di-force.
     */
    public function fire(GtrCategory $category, User $by, ?Carbon $at = null, bool $force = false): array
    {
        return DB::transaction(function () use ($category, $by, $at, $force) {
            $locked = GtrCategory::whereKey($category->id)->lockForUpdate()->firstOrFail();

            if ($locked->gun_fired_at && ! $force) {
                return ['fired' => false, 'category' => $locked];
            }

            $locked->forceFill([
                'gun_fired_at' => $at ?? now(),
                'gun_fired_by' => $by->id,
            ])->saveQuietly(); // tanpa hook saved → tidak memicu GtrTimingSync

            return ['fired' => true, 'category' => $locked];
        });
    }

    /**
     * Start BERSAMA: semua kategori aktif ditembak pada jam yang sama persis (GTR: 7K & 15K start
     * bareng). fired=false bila ada kategori yang sudah ditembak dan tidak di-force.
     *
     * @return array{fired: bool, categories: Collection<int, GtrCategory>}
     */
    public function fireAll(User $by, ?Carbon $at = null, bool $force = false): array
    {
        return DB::transaction(function () use ($by, $at, $force) {
            $cats = GtrCategory::where('is_active', true)->orderBy('sort_order')->orderBy('id')->lockForUpdate()->get();

            if (! $force && $cats->contains(fn (GtrCategory $c) => $c->gun_fired_at !== null)) {
                return ['fired' => false, 'categories' => $cats];
            }

            $at ??= now();
            foreach ($cats as $c) {
                $c->forceFill(['gun_fired_at' => $at, 'gun_fired_by' => $by->id])->saveQuietly();
            }

            return ['fired' => true, 'categories' => $cats];
        });
    }
}
