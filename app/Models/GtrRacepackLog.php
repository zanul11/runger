<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Satu penyerahan race pack. Dibatalkan = cancelled_at/by terisi (baris tidak dihapus). */
class GtrRacepackLog extends Model
{
    protected $fillable = ['gtr_registration_id', 'collected_at', 'collected_by', 'picked_by', 'cancelled_at', 'cancelled_by'];

    protected function casts(): array
    {
        return ['collected_at' => 'datetime', 'cancelled_at' => 'datetime'];
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(GtrRegistration::class, 'gtr_registration_id');
    }

    public function collector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collected_by');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }
}
