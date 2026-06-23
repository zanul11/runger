<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GtrPayment extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'amount' => 'integer',
        'expires_at' => 'datetime',
        'paid_at' => 'datetime',
        'raw' => 'array',
    ];

    public function registration(): BelongsTo
    {
        return $this->belongsTo(GtrRegistration::class, 'gtr_registration_id');
    }

    /** Snap masih bisa dipakai lagi (pending & belum kedaluwarsa). */
    public function isReusable(): bool
    {
        return $this->status === 'pending'
            && $this->snap_redirect_url
            && (! $this->expires_at || $this->expires_at->isFuture());
    }
}
