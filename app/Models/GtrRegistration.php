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
        'discount_consumed' => 'boolean',
        'discount_amount' => 'integer',
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
            // BIB + email konfirmasi bayar bila dibuat sudah lunas (mis. via admin).
            if ($reg->payment_status === 'paid') {
                self::assignBib($reg);
                $reg->fresh()->sendPaymentConfirmation();
            }
        });

        // Saat pembayaran berubah menjadi LUNAS (webhook / admin): BIB + email.
        static::updated(function (GtrRegistration $reg) {
            if ($reg->wasChanged('payment_status')) {
                if ($reg->payment_status === 'paid') {
                    self::assignBib($reg);
                    $reg->fresh()->sendPaymentConfirmation();
                }
                self::reconcileDiscount($reg);
            }
        });

        // Registrasi dihapus → kembalikan slot kuota voucher bila sedang dipakai.
        static::deleted(function (GtrRegistration $reg) {
            if ($reg->gtr_discount_id && $reg->discount_consumed) {
                optional($reg->discount)->markReleased();
            }
        });
    }

    /**
     * Sinkronkan pemakaian kuota voucher dengan status pembayaran:
     *  - status cancelled  → kembalikan slot (jika sedang dipakai)
     *  - status aktif lagi → pakai slot kembali (jika sebelumnya dikembalikan)
     * Flag discount_consumed menjaga agar tidak dobel kurang/tambah (idempoten).
     */
    protected static function reconcileDiscount(GtrRegistration $reg): void
    {
        if (! $reg->gtr_discount_id) {
            return;
        }

        $shouldConsume = $reg->payment_status !== 'cancelled';

        if ($shouldConsume && ! $reg->discount_consumed) {
            optional($reg->discount)->markUsed();
            $reg->forceFill(['discount_consumed' => true])->saveQuietly();
        } elseif (! $shouldConsume && $reg->discount_consumed) {
            optional($reg->discount)->markReleased();
            $reg->forceFill(['discount_consumed' => false])->saveQuietly();
        }
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

    /** Biaya admin / platform aplikasi (Rupiah) — hanya berlaku untuk pembayaran QRIS. */
    public const ADMIN_FEE = 2500;

    /** Metode pembayaran. Pendaftaran via sistem selalu QRIS; sisanya hanya via admin. */
    public const PAY_QRIS = 'QRIS';

    public const PAY_METHODS = [
        'QRIS' => 'QRIS (online)',
        'Cash' => 'Tunai (Cash)',
        'Transfer Bank' => 'Transfer Bank',
        'Free' => 'Gratis / Undangan',
    ];

    /** Apakah metode pembayarannya QRIS? */
    public function isQris(): bool
    {
        return str_contains(mb_strtolower((string) $this->pay), 'qris');
    }

    /** Biaya layanan: hanya dikenakan untuk QRIS; metode lain = 0. */
    public function serviceFee(): int
    {
        return $this->isQris() ? self::ADMIN_FEE : 0;
    }

    /**
     * Data laporan pembayaran: pendaftar lunas, total uang masuk, dan rincian
     * per metode + per kategori. Dipakai halaman admin & versi cetak.
     */
    public static function paymentReport(): array
    {
        $paid = static::with('category')->where('payment_status', 'paid')->get();

        $byMethod = $paid
            ->groupBy(fn (self $r) => $r->pay ?: 'Lainnya')
            ->map(fn ($g, $method) => [
                'method' => $method,
                'count' => $g->count(),
                'total' => (int) $g->sum(fn (self $r) => $r->totalAmount()),
            ])
            ->sortByDesc('total')->values()->all();

        $byCategory = $paid
            ->groupBy(fn (self $r) => $r->category?->name ?: '—')
            ->map(fn ($g, $cat) => [
                'category' => $cat,
                'count' => $g->count(),
                'total' => (int) $g->sum(fn (self $r) => $r->totalAmount()),
            ])
            ->sortByDesc('total')->values()->all();

        return [
            'count' => $paid->count(),
            'total' => (int) $paid->sum(fn (self $r) => $r->totalAmount()),
            'pending' => static::where('payment_status', 'pending')->count(),
            'cancelled' => static::where('payment_status', 'cancelled')->count(),
            'by_method' => $byMethod,
            'by_category' => $byCategory,
        ];
    }

    public function discount(): BelongsTo
    {
        return $this->belongsTo(GtrDiscount::class, 'gtr_discount_id');
    }

    /**
     * Biaya pendaftaran NETO (setelah diskon, tanpa admin fee). Bila sudah terkunci
     * (amount terisi saat dibayar) pakai itu; kalau belum, harga kategori berlaku
     * SEKARANG dikurangi potongan voucher.
     */
    public function baseAmount(): int
    {
        if ($this->amount) {
            return (int) $this->amount;
        }

        $price = (int) ($this->category?->currentPrice() ?? 0);

        return max(0, $price - (int) $this->discount_amount);
    }

    /** Total tagihan = biaya pendaftaran + biaya layanan (0 bila non-QRIS). */
    public function totalAmount(): int
    {
        return $this->baseAmount() + $this->serviceFee();
    }

    /** Kirim email KONFIRMASI PENDAFTARAN (aman: tak menggagalkan proses bila error). */
    public function sendConfirmationEmail(): bool
    {
        return $this->sendMailSafe(new \App\Mail\RegistrationConfirmation($this->fresh('category')));
    }

    /** Kirim email KONFIRMASI PEMBAYARAN / e-ticket (dipanggil otomatis saat lunas). */
    public function sendPaymentConfirmation(): bool
    {
        return $this->sendMailSafe(new \App\Mail\PaymentConfirmation($this->fresh('category')));
    }

    private function sendMailSafe(\Illuminate\Mail\Mailable $mailable): bool
    {
        if (! $this->email) {
            return false;
        }

        try {
            \Illuminate\Support\Facades\Mail::to($this->email)->send($mailable);

            return true;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Gagal kirim email GTR: ' . $e->getMessage());

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
