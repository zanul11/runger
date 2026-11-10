<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Audit koreksi manual hasil (siapa, field apa, nilai lama → baru, alasan). */
class GtrResultAdjustment extends Model
{
    protected $guarded = ['id'];

    public const FIELD_STATUS = 'status';

    public const FIELD_START_TIME = 'start_time';

    public const FIELD_FINISH_TIME = 'finish_time';

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function registration(): BelongsTo
    {
        return $this->belongsTo(GtrRegistration::class, 'gtr_registration_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
