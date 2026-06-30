<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GtrTimingPoint extends Model
{
    protected $guarded = ['id'];

    public const TYPE_START = 'start';
    public const TYPE_CHECKPOINT = 'checkpoint';
    public const TYPE_WATER_STATION = 'water_station';
    public const TYPE_FINISH = 'finish';

    public const TYPES = [
        self::TYPE_START => 'Start',
        self::TYPE_CHECKPOINT => 'Checkpoint',
        self::TYPE_WATER_STATION => 'Water Station',
        self::TYPE_FINISH => 'Finish',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(GtrCategory::class, 'gtr_category_timing_point')
            ->withPivot(['sequence', 'is_mandatory', 'cutoff_at'])
            ->withTimestamps();
    }

    public function scanLogs(): HasMany
    {
        return $this->hasMany(GtrScanLog::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(GtrTimingPointAssignment::class);
    }
}
