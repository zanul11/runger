<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GtrScanLog extends Model
{
    protected $guarded = ['id'];

    public const SOURCE_SCAN = 'scan';
    public const SOURCE_MANUAL = 'manual';
    public const SOURCE_VIDEO = 'video';

    protected $casts = [
        'scanned_at' => 'datetime',
        'raw_device_time' => 'datetime',
        'clock_offset_ms' => 'integer',
        'is_flagged' => 'boolean',
    ];

    public function registration(): BelongsTo
    {
        return $this->belongsTo(GtrRegistration::class, 'gtr_registration_id');
    }

    public function timingPoint(): BelongsTo
    {
        return $this->belongsTo(GtrTimingPoint::class, 'gtr_timing_point_id');
    }
}
