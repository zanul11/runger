<?php

namespace App\Models;

use App\Casts\DateTimeMs;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Log scan APPEND-ONLY. Baris tidak pernah dihapus; scan yang tidak dihitung ditandai
 * is_ignored + ignore_reason. Waktu presisi milidetik (zona app, Asia/Makassar):
 *  - raw_device_time    = jam device saat input
 *  - scanned_at         = waktu terkoreksi = raw_device_time + clock_offset_ms (dipakai result engine)
 *  - server_received_at = kapan server menerima
 */
class GtrScanLog extends Model
{
    protected $guarded = ['id'];

    public const SOURCE_QR = 'qr';

    public const SOURCE_MANUAL = 'manual';

    public const SOURCE_VIDEO = 'video';

    /** Sumber yang boleh dikirim scanner (QR atau keypad manual). */
    public const SCANNER_SOURCES = [self::SOURCE_QR, self::SOURCE_MANUAL];

    /** Alasan scan diabaikan. */
    public const REASON_DUPLICATE = 'duplicate';         // BIB sama, pos sama, < 60 detik (otomatis)

    public const REASON_CANCELLED = 'cancelled';         // dibatalkan marshal ("batalkan")

    public const REASON_UNKNOWN_BIB = 'unknown_bib';     // BIB tidak terdaftar / belum lunas

    public const REASON_INVALID_BIB = 'invalid_bib';     // input bukan angka

    public const REASON_WRONG_CATEGORY = 'wrong_category'; // kategori peserta tidak melewati pos ini

    public const REASON_ADMIN = 'admin';                 // diabaikan admin

    public const REASONS = [
        self::REASON_DUPLICATE => 'Duplikat (< 60 detik)',
        self::REASON_CANCELLED => 'Dibatalkan marshal',
        self::REASON_UNKNOWN_BIB => 'BIB tidak terdaftar',
        self::REASON_INVALID_BIB => 'BIB tidak valid',
        self::REASON_WRONG_CATEGORY => 'Salah kategori / pos',
        self::REASON_ADMIN => 'Diabaikan admin',
    ];

    /** Jendela dedup: scan BIB yang sama di pos yang sama dalam rentang ini diabaikan. */
    public const DEDUP_WINDOW_MS = 60_000;

    protected $casts = [
        'scanned_at' => DateTimeMs::class,
        'raw_device_time' => DateTimeMs::class,
        'server_received_at' => DateTimeMs::class,
        'ignored_at' => 'datetime',
        'clock_offset_ms' => 'integer',
        'pulltag_seq' => 'integer',
        'is_flagged' => 'boolean',
        'is_ignored' => 'boolean',
    ];

    /** Scan yang dihitung: terhubung ke peserta & tidak diabaikan. */
    public function scopeValid(Builder $query): Builder
    {
        return $query->where('is_ignored', false)->whereNotNull('gtr_registration_id');
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(GtrRegistration::class, 'gtr_registration_id');
    }

    public function timingPoint(): BelongsTo
    {
        return $this->belongsTo(GtrTimingPoint::class, 'gtr_timing_point_id');
    }

    /** Petugas (marshal) yang melakukan scan. */
    public function scanner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scanned_by');
    }

    public function ignoredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ignored_by');
    }
}
