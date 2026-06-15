<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class GtrOverview extends Model
{
    protected $guarded = ['id'];

    public function getPhotoMainUrlAttribute(): string
    {
        return $this->photo_main
            ? Storage::disk('public')->url($this->photo_main)
            : asset('assets/gtr/WhatsApp Image 2026-06-07 at 09.22.00.jpeg');
    }

    public function getPhoto2UrlAttribute(): string
    {
        return $this->photo_2
            ? Storage::disk('public')->url($this->photo_2)
            : asset('assets/gtr/WhatsApp Image 2026-06-06 at 13.37.28.jpeg');
    }

    public function getPhoto3UrlAttribute(): string
    {
        return $this->photo_3
            ? Storage::disk('public')->url($this->photo_3)
            : asset('assets/g-sunset.jpeg');
    }

    /** The single overview row (creates one if missing). */
    public static function current(): self
    {
        return static::first() ?? static::create([]);
    }
}
