<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Volunteer extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'interests' => 'array',
    ];

    public const INTERESTS = [
        'acara' => 'Acara',
        'humas' => 'Humas',
        'perlengkapan' => 'Perlengkapan',
        'pubdekdok' => 'Pubdekdok',
    ];
}
