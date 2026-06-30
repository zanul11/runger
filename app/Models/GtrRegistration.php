<?php

namespace App\Models;

use App\Support\Phone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    /** Status lomba (kolom race_status). */
    public const RACE_REGISTERED = 'registered';
    public const RACE_DNS = 'dns';
    public const RACE_DNF = 'dnf';
    public const RACE_DQ = 'dq';
    public const RACE_FINISHER = 'finisher';

    /** Beri qr_token acak otomatis saat dibuat bila belum diisi. */
    protected static function booted(): void
    {
        static::creating(function (GtrRegistration $reg) {
            if (empty($reg->qr_token)) {
                $reg->qr_token = self::generateQrToken();
            }
        });
    }

    /** Token QR unik & acak (URL-safe). */
    public static function generateQrToken(): string
    {
        do {
            $token = 'GTR-' . strtoupper(\Illuminate\Support\Str::random(10));
        } while (self::where('qr_token', $token)->exists());

        return $token;
    }

    /** Normalisasi gender ke "M"/"F" untuk roster & ranking. */
    public function genderCode(): ?string
    {
        return match (true) {
            str_starts_with(mb_strtolower((string) $this->gender), 'l') => 'M',
            str_starts_with(mb_strtolower((string) $this->gender), 'p') => 'F',
            default => null,
        };
    }

    /** Biaya admin / platform aplikasi (Rupiah) yang ditambahkan ke setiap pembayaran. */
    public const ADMIN_FEE = 2500;

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

    public function scanLogs(): HasMany
    {
        return $this->hasMany(GtrScanLog::class, 'gtr_registration_id');
    }

    public function result(): HasOne
    {
        return $this->hasOne(GtrResult::class, 'gtr_registration_id');
    }
}
