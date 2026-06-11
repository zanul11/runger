<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GtrRegistration extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'birth_date' => 'date',
        'registered_at' => 'datetime',
        'paid_at' => 'datetime',
        'agree_terms' => 'boolean',
    ];

    public const SIZES = ['XS', 'S', 'M', 'L', 'XL', '2XL', '3XL', '4XL', '5XL'];
    public const BLOOD_TYPES = ['A', 'B', 'AB', 'O'];
    public const GENDERS = ['Laki-laki', 'Perempuan'];

    public function runner(): BelongsTo
    {
        return $this->belongsTo(Runner::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(GtrCategory::class, 'gtr_category_id');
    }
}
