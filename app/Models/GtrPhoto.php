<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class GtrPhoto extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'bibs' => 'array',
        'is_published' => 'boolean',
    ];

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->path);
    }

    /** Teks BIB dipisah koma (untuk input cepat di admin). */
    public function getBibTextAttribute(): string
    {
        return implode(', ', $this->bibs ?? []);
    }

    /** Normalisasi input BIB "5004, 5010 7001" → ["5004","5010","7001"]. */
    public static function parseBibs(?string $text): array
    {
        return collect(preg_split('/[\s,;]+/', (string) $text))
            ->map(fn ($b) => trim($b))
            ->filter(fn ($b) => $b !== '' && preg_match('/^[A-Za-z0-9-]+$/', $b))
            ->unique()->values()->all();
    }
}
