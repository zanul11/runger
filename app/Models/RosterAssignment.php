<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu slot petugas di pos roster. Slot dianggap TERISI bila `nama` tidak kosong; baris boleh
 * tetap ada dengan nama kosong (mis. setelah dihapus) supaya jejak `updated_by` tidak hilang.
 */
class RosterAssignment extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['slot_no' => 'integer'];

    public function position(): BelongsTo
    {
        return $this->belongsTo(RosterPosition::class, 'position_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function isFilled(): bool
    {
        return trim((string) $this->nama) !== '';
    }

    /** Query: hanya slot terisi. */
    public function scopeFilled($q)
    {
        return $q->whereNotNull('nama')->where('nama', '!=', '');
    }
}
