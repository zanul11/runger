<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Grup dalam satu area roster (mis. "Komando", "Marshal"). */
class RosterGroup extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['urutan' => 'integer'];

    public function area(): BelongsTo
    {
        return $this->belongsTo(RosterArea::class, 'area_id');
    }

    public function positions(): HasMany
    {
        return $this->hasMany(RosterPosition::class, 'group_id')->orderBy('urutan')->orderBy('id');
    }
}
