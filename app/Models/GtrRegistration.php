<?php

namespace App\Models;

use App\Support\Phone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    /** Simpan nomor HP peserta dalam format kanonik (62…). */
    public function setWhatsappAttribute($value): void
    {
        $this->attributes['whatsapp'] = $value ? Phone::normalize($value) : $value;
    }

    /** Simpan kontak darurat dalam format kanonik (62…). */
    public function setEmergencyContactAttribute($value): void
    {
        $this->attributes['emergency_contact'] = $value ? Phone::normalize($value) : $value;
    }

    public function runner(): BelongsTo
    {
        return $this->belongsTo(Runner::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(GtrCategory::class, 'gtr_category_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(GtrPayment::class, 'gtr_registration_id');
    }
}
