<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Pengeluaran panitia GTR (diinput bendahara di GTR Crew → Pengeluaran).
 * Kwitansi disimpan di disk "local" (privat) — dibuka lewat API ber-token, bukan URL publik.
 */
class GtrExpense extends Model
{
    public const DISK = 'local';

    public const DIR = 'gtr/expenses';

    protected $fillable = ['spent_on', 'amount', 'pic', 'description', 'receipt_path', 'receipt_mime', 'created_by', 'updated_by'];

    protected function casts(): array
    {
        return [
            'spent_on' => 'date',
            'amount' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::deleted(fn (GtrExpense $e) => $e->deleteReceipt());
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function hasReceipt(): bool
    {
        return $this->receipt_path !== null && Storage::disk(self::DISK)->exists($this->receipt_path);
    }

    public function deleteReceipt(): void
    {
        if ($this->receipt_path) {
            Storage::disk(self::DISK)->delete($this->receipt_path);
        }
    }
}
