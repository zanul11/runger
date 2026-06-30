<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GtrResult extends Model
{
    protected $guarded = ['id'];

    public const STATUS_FINISHER = 'finisher';
    public const STATUS_DNF = 'dnf';
    public const STATUS_DQ = 'dq';
    public const STATUS_DNS = 'dns';

    protected $casts = [
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
