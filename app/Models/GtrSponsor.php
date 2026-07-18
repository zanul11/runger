<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class GtrSponsor extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public const TIERS = [
        'title' => 'Title Sponsor',
        'gold' => 'Gold',
        'silver' => 'Silver',
        'partner' => 'Partner',
    ];

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo ? Storage::disk('public')->url($this->logo) : null;
    }
}
