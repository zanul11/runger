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
            : asset('assets/gtr/overview-1.webp');
    }

    public function getPhoto2UrlAttribute(): string
    {
        return $this->photo_2
            ? Storage::disk('public')->url($this->photo_2)
            : asset('assets/gtr/overview-2.webp');
    }

    public function getPhoto3UrlAttribute(): string
    {
        return $this->photo_3
            ? Storage::disk('public')->url($this->photo_3)
            : asset('assets/gtr/overview-3.webp');
    }

    /** The single overview row (creates one if missing). */
    public static function current(): self
    {
        return static::first() ?? static::create([]);
    }
}
