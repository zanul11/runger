<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class GtrSetting extends Model
{
    protected $guarded = ['id'];

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
