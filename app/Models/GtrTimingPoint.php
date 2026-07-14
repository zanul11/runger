<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
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

    /**
     * Kode digenerate otomatis dari tipe bila kosong (unik global —
     * sistem single-event, tak ada event_id).
     */
    protected static function booted(): void
    {
        static::creating(function (GtrTimingPoint $tp) {
            if (empty($tp->code) && $tp->type) {
                $tp->code = self::generateCode($tp->type);
            }
        });
    }

    /** Kode otomatis sesuai tipe: START / FINISH / CP1.. / WS1.. */
    public static function generateCode(string $type): string
    {
        return match ($type) {
            self::TYPE_START => self::nextCode('START', true),
            self::TYPE_FINISH => self::nextCode('FINISH', true),
            self::TYPE_CHECKPOINT => self::nextCode('CP'),
            self::TYPE_WATER_STATION => self::nextCode('WS'),
            default => self::nextCode('TP'),
        };
    }

    private static function nextCode(string $prefix, bool $bareFirst = false): string
    {
        $exists = fn (string $code) => self::where('code', $code)->exists();

        if ($bareFirst && ! $exists($prefix)) {
            return $prefix;
        }

        $n = 1;
        while ($exists($prefix . $n)) {
            $n++;
        }

        return $prefix . $n;
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
