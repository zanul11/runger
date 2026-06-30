<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'date' => 'date',
        'default_gun_start' => 'datetime',
        'is_featured' => 'boolean',
        'is_published' => 'boolean',
        'is_coming_soon' => 'boolean',
        'tikum_lat' => 'decimal:7',
        'tikum_lng' => 'decimal:7',
    ];

    protected $appends = ['status'];

    /**
     * Status di-derive otomatis:
     *   - is_coming_soon flag → 'coming_soon'
     *   - date+time sudah lewat → 'completed'
     *   - selain itu → 'upcoming'
     */
    protected function status(): Attribute
    {
        return Attribute::get(function () {
            if ($this->is_coming_soon) {
                return 'coming_soon';
            }
            if ($this->date) {
                $eventDt = \Carbon\Carbon::parse($this->date->format('Y-m-d') . ' ' . ($this->time ?? '23:59'), 'Asia/Makassar');
                if ($eventDt->isPast()) {
                    return 'completed';
                }
            }
            return 'upcoming';
        });
    }

    public function raceCategories(): HasMany
    {
        return $this->hasMany(RaceCategory::class)->orderBy('sort_order');
    }

    public function rules(): HasMany
    {
        return $this->hasMany(Rule::class)->orderBy('number');
    }

    public function sponsors(): HasMany
    {
        return $this->hasMany(Sponsor::class)
            ->where('is_active', true)
            ->orderByRaw("FIELD(tier,'title','gold','silver','partner')")
            ->orderBy('sort_order');
    }

    public function routes(): HasMany
    {
        return $this->hasMany(Route::class)->orderBy('sort_order');
    }

    public function gallery(): HasMany
    {
        return $this->hasMany(GalleryItem::class)->orderBy('sort_order');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(Result::class);
    }

    public function timingPoints(): HasMany
    {
        return $this->hasMany(GtrTimingPoint::class)->orderBy('sort_order');
    }
}
