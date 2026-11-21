<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Event extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'date' => 'date',
        'default_gun_start' => 'datetime',
        'is_featured' => 'boolean',
        'is_published' => 'boolean',
        'is_coming_soon' => 'boolean',
        'roster_aktif' => 'boolean',
        'tikum_lat' => 'decimal:7',
        'tikum_lng' => 'decimal:7',
    ];

    protected $appends = ['status'];

    protected static function booted(): void
    {
        // Roster panitia: hanya satu event aktif.
        static::saved(function (Event $event) {
            if ($event->roster_aktif && ($event->wasRecentlyCreated || $event->wasChanged('roster_aktif'))) {
                static::whereKeyNot($event->id)->where('roster_aktif', true)->update(['roster_aktif' => false]);
            }
        });
    }

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
                $eventDt = Carbon::parse($this->date->format('Y-m-d').' '.($this->time ?? '23:59'), 'Asia/Makassar');
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

    /** Roster panitia hari H: area → grup → pos. */
    public function rosterAreas(): HasMany
    {
        return $this->hasMany(RosterArea::class)->orderBy('urutan')->orderBy('id');
    }

    public function rosterConsumption(): HasOne
    {
        return $this->hasOne(RosterConsumptionSetting::class);
    }

    /**
     * Event yang tampil di GTR Crew → Roster: yang ditandai roster_aktif, lalu event yang sudah
     * punya struktur roster, lalu event GTR.
     */
    public static function rosterActive(): ?self
    {
        return static::where('roster_aktif', true)->first()
            ?? static::whereHas('rosterAreas')->latest('date')->first()
            ?? static::find(static::gtrId());
    }

    /** Slug event GTR (modul race-timing single-event). */
    public const GTR_SLUG = 'gerung-trail-run-2026';

    /** ID event GTR (default), fallback ke event pertama. */
    public static function gtrId(): ?int
    {
        return static::where('slug', self::GTR_SLUG)->value('id')
            ?? static::orderBy('id')->value('id');
    }
}
