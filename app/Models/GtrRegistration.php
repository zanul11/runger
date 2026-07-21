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

    protected static function booted(): void
    {
        static::created(function (GtrRegistration $reg) {
            // Nomor registrasi (identitas QR) untuk semua jalur pembuatan.
            if (empty($reg->nomor_registrasi)) {
                $reg->forceFill([
                    'nomor_registrasi' => 'GTR2026' . str_pad((string) $reg->id, 5, '0', STR_PAD_LEFT),
                ])->saveQuietly();
            }
            // BIB langsung bila dibuat sudah lunas (mis. via admin).
            if ($reg->payment_status === 'paid') {
                self::assignBib($reg);
            }
        });

        // BIB otomatis saat pembayaran berubah menjadi LUNAS (webhook / admin).
        static::updated(function (GtrRegistration $reg) {
            if ($reg->wasChanged('payment_status') && $reg->payment_status === 'paid') {
                self::assignBib($reg);
            }
        });
    }

    /** Beri nomor BIB bila belum ada (prefix kategori + urutan per kategori). */
    protected static function assignBib(GtrRegistration $reg): void
    {
        if (empty($reg->bib_number)) {
            app(\App\Services\BibNumberService::class)->assignFor($reg);
        }
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

    /**
     * Biaya pendaftaran (tanpa admin fee). Bila sudah terkunci (amount terisi saat
     * dibayar) pakai itu; kalau belum, pakai harga kategori yang berlaku SEKARANG
     * (early bird bila aktif, selain itu normal).
     */
    public function baseAmount(): int
    {
        return (int) ($this->amount ?: ($this->category?->currentPrice() ?? 0));
    }

    /** Total tagihan = biaya pendaftaran + biaya admin. */
    public function totalAmount(): int
    {
        return $this->baseAmount() + self::ADMIN_FEE;
    }

    /** Kirim email konfirmasi ke peserta (aman: tak menggagalkan proses bila error). */
    public function sendConfirmationEmail(): bool
    {
        if (! $this->email) {
            return false;
        }

        try {
            \Illuminate\Support\Facades\Mail::to($this->email)
                ->send(new \App\Mail\RegistrationConfirmation($this->fresh('category')));

            return true;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Gagal kirim email konfirmasi GTR: ' . $e->getMessage());

            return false;
        }
    }

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
