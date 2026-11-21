<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Str;

/** Area roster panitia (mis. "Race Village", "Lapangan"). */
class RosterArea extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['urutan' => 'integer'];

    protected static function booted(): void
    {
        // Slug otomatis dari nama, unik per event (dipakai area acuan konsumsi & tab roster).
        static::saving(function (RosterArea $area) {
            if (blank($area->slug)) {
                $base = Str::slug($area->nama) ?: 'area';
                $slug = $base;
                $i = 2;
                while (static::where('event_id', $area->event_id)->where('slug', $slug)->whereKeyNot($area->getKey())->exists()) {
                    $slug = $base.'-'.$i++;
                }
                $area->slug = $slug;
            }
        });
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function groups(): HasMany
    {
        return $this->hasMany(RosterGroup::class, 'area_id')->orderBy('urutan')->orderBy('id');
    }

    public function positions(): HasManyThrough
    {
        return $this->hasManyThrough(RosterPosition::class, RosterGroup::class, 'area_id', 'group_id');
    }
}
