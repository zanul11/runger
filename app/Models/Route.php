<?php

namespace App\Models;

use App\Services\GpxParser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Route extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'route_points' => 'array',
        'km_markers' => 'array',
        'elevation_profile' => 'array',
        'total_km' => 'decimal:2',
        'tikum_lat' => 'decimal:7',
        'tikum_lng' => 'decimal:7',
        'is_active' => 'boolean',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Auto-parse GPX file whenever gpx_file changes,
     * filling derived stats and shape data.
     */
    protected static function booted(): void
    {
        static::saving(function (Route $route) {
            if (! $route->isDirty('gpx_file') || ! $route->gpx_file) {
                return;
            }

            $disk = Storage::disk('public');
            if (! $disk->exists($route->gpx_file)) {
                return;
            }

            $abs = $disk->path($route->gpx_file);
            $parsed = GpxParser::parse($abs);
            if (empty($parsed)) {
                return;
            }

            // Use the GPX track <name> if user didn't set one
            if (empty($route->name) && ! empty($parsed['name'])) {
                $route->name = $parsed['name'];
            }
            if (empty($route->slug)) {
                $route->slug = Str::slug($route->name ?: pathinfo($route->gpx_file, PATHINFO_FILENAME));
            }

            $route->total_km = $parsed['total_km'];
            $route->elevation_gain_m = $parsed['elevation_gain_m'];
            $route->elevation_min_m = $parsed['elevation_min_m'];
            $route->elevation_max_m = $parsed['elevation_max_m'];
            $route->km_marker_count = $parsed['km_marker_count'];
            $route->tikum_lat = $parsed['tikum_lat'];
            $route->tikum_lng = $parsed['tikum_lng'];
            $route->route_points = $parsed['route_points'];
            $route->km_markers = $parsed['km_markers'];
            $route->elevation_profile = $parsed['elevation_profile'];
        });
    }
}
