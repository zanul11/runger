<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class GtrSetting extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'results_published' => 'boolean',
        'race_guide_slides' => 'array',
        'race_guide_published_at' => 'datetime',
        'registration_closes_at' => 'datetime',
    ];

    /** Pendaftaran online sudah ditutup (lewat registration_closes_at)? */
    public static function registrationClosed(): bool
    {
        $closesAt = static::first()?->registration_closes_at;

        return $closesAt !== null && now()->greaterThan($closesAt);
    }

    /** Race guide final sudah dipublikasikan ke website? */
    public function raceGuidePublished(): bool
    {
        return $this->race_guide_published_at !== null && ! empty($this->race_guide_slides);
    }

    /** Teks multi-baris → daftar poin (baris kosong & bullet "-" / "•" dibuang). */
    public static function lines(?string $text): array
    {
        return collect(preg_split('/\r\n|\r|\n/', (string) $text))
            ->map(fn ($l) => trim(preg_replace('/^\s*(?:[-•*]\s*|\d+[.)]\s+)/u', '', $l)))
            ->filter()
            ->values()
            ->all();
    }

    public function getHeaderUrlAttribute(): string
    {
        return $this->header_image
            ? Storage::disk('public')->url($this->header_image)
            : asset('assets/gtr/gallery/ari.jpeg');
    }

    /** The single settings row (creates one if missing). */
    public static function current(): self
    {
        return static::first() ?? static::create([]);
    }
}
