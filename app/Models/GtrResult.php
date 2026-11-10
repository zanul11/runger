<?php

namespace App\Models;

use App\Casts\DateTimeMs;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GtrResult extends Model
{
    protected $guarded = ['id'];

    public const STATUS_FINISHER = 'finisher';

    public const STATUS_DNF = 'dnf';

    public const STATUS_DQ = 'dq';

    public const STATUS_DNS = 'dns';

    protected $casts = [
        'start_time' => DateTimeMs::class,
        'finish_time' => DateTimeMs::class,
        'gun_time_ms' => 'integer',
        'net_time_ms' => 'integer',
        'checkpoints_json' => 'array',
        'needs_review' => 'boolean',
        'gun_time_seconds' => 'integer',
        'net_time_seconds' => 'integer',
        'rank_overall' => 'integer',
        'rank_category' => 'integer',
        'rank_gender' => 'integer',
        'computed_at' => 'datetime',
    ];

    public function registration(): BelongsTo
    {
        return $this->belongsTo(GtrRegistration::class, 'gtr_registration_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(GtrCategory::class, 'gtr_category_id');
    }

    /** Koreksi manual untuk peserta hasil ini (lihat GtrResultAdjustment). */
    public function adjustments(): HasMany
    {
        return $this->hasMany(GtrResultAdjustment::class, 'gtr_registration_id', 'gtr_registration_id');
    }

    /** Format milidetik -> "HH:MM:SS.mmm" (admin) atau "HH:MM:SS" ($withMs=false, publik; dibulatkan ke bawah). */
    public static function formatMs(?int $ms, bool $withMs = true): ?string
    {
        if ($ms === null) {
            return null;
        }
        $s = intdiv($ms, 1000);
        $base = sprintf('%02d:%02d:%02d', intdiv($s, 3600), intdiv($s % 3600, 60), $s % 60);

        return $withMs ? $base.sprintf('.%03d', $ms % 1000) : $base;
    }

    /** Format detik -> "HH:MM:SS". */
    public static function formatSeconds(?int $seconds): ?string
    {
        if ($seconds === null) {
            return null;
        }

        return sprintf('%02d:%02d:%02d', intdiv($seconds, 3600), intdiv($seconds % 3600, 60), $seconds % 60);
    }

    public function getNetTimeFormattedAttribute(): ?string
    {
        return self::formatSeconds($this->net_time_seconds);
    }

    public function getGunTimeFormattedAttribute(): ?string
    {
        return self::formatSeconds($this->gun_time_seconds);
    }
}
