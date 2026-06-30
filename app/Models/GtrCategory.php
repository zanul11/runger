<?php

namespace App\Models;

use App\Services\GpxParser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class GtrCategory extends Model
{
    protected $guarded = ['id'];

    public function registrations(): HasMany
    {
        return $this->hasMany(GtrRegistration::class, 'gtr_category_id');
    }

    /** Titik timing yang harus dilewati kategori ini, urut sesuai sequence. */
    public function timingPoints(): BelongsToMany
    {
        return $this->belongsToMany(GtrTimingPoint::class, 'gtr_category_timing_point')
            ->withPivot(['sequence', 'is_mandatory', 'cutoff_at'])
            ->withTimestamps()
            ->orderBy('gtr_category_timing_point.sequence');
    }

    protected $casts = [
        'route_points' => 'array',
        'elevation_profile' => 'array',
        'water_stations' => 'array',
        'mandatory_gear' => 'array',
        'rundown' => 'array',
        'early_bird_until' => 'date',
        'gun_start' => 'datetime',
        'cut_off_at' => 'datetime',
        'total_km' => 'decimal:2',
        'price_early_bird' => 'integer',
        'price_normal' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Parse the uploaded GPX into route points (for the course map) whenever it changes.
     */
    protected static function booted(): void
    {
        static::saving(function (GtrCategory $cat) {
            if (! $cat->isDirty('gpx_file')) {
                return;
            }

            if (! $cat->gpx_file) {
                $cat->route_points = null;
                $cat->elevation_profile = null;
                $cat->total_km = null;

                return;
            }

            $disk = Storage::disk('public');
            if (! $disk->exists($cat->gpx_file)) {
                return;
            }

            $parsed = GpxParser::parse($disk->path($cat->gpx_file));
            $cat->route_points = $parsed['route_points'] ?? null;
            $cat->elevation_profile = $parsed['elevation_profile'] ?? null;
            $cat->total_km = $parsed['total_km'] ?? null;
        });
    }

    public function getHeaderUrlAttribute(): string
    {
        return $this->header_image
            ? Storage::disk('public')->url($this->header_image)
            : asset('assets/gtr/gallery/rudi.jpeg');
    }

    public function getGpxUrlAttribute(): ?string
    {
        return $this->gpx_file ? Storage::disk('public')->url($this->gpx_file) : null;
    }

    /**
     * Elevation gain read live from the GPX file (cached by file mtime), never from a DB column.
     * Returns a formatted string like "+344 M", or null when there is no GPX / no elevation data.
     */
    public function gpxElevationGain(): ?string
    {
        if (! $this->gpx_file) {
            return null;
        }

        $disk = Storage::disk('public');
        if (! $disk->exists($this->gpx_file)) {
            return null;
        }

        $abs = $disk->path($this->gpx_file);
        $gain = Cache::remember(
            'gtr_eg_' . md5($this->gpx_file . '|' . @filemtime($abs)),
            3600,
            fn () => GpxParser::parse($abs)['elevation_gain_m'] ?? null,
        );

        return $gain !== null ? '+' . round($gain) . ' M' : null;
    }

    public function priceFormatted(?int $value): string
    {
        return $value ? 'IDR ' . number_format($value, 0, ',', '.') : '-';
    }

    public function getEarlyBirdFormattedAttribute(): string
    {
        return $this->priceFormatted($this->price_early_bird);
    }

    public function getWaterStationCountAttribute(): int
    {
        return count($this->water_stations ?? []);
    }

    public function getWaterStationLabelAttribute(): string
    {
        $count = $this->water_station_count;

        return $count > 0 ? $count . ' Pos' : ($this->water_station ?: '-');
    }

    public function getNormalFormattedAttribute(): string
    {
        return $this->priceFormatted($this->price_normal);
    }
}
