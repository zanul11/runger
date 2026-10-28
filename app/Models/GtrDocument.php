<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Dokumen peserta GTR (Waiver, PAR-Q, Surat Kuasa, dll.). Isi per bahasa (body_id / body_en) berupa
 * HTML dari editor teks dan dirender ke PDF saat dipublikasikan (lihat App\Support\GtrDocumentPdf).
 */
class GtrDocument extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'has_en' => 'boolean',
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    public const LANGS = ['id' => 'Indonesia', 'en' => 'English'];

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('is_published', true)->whereNotNull('pdf_id')->orderBy('sort_order')->orderBy('id');
    }

    /** Bahasa yang tersedia untuk dokumen ini. */
    public function langs(): array
    {
        return $this->has_en ? ['id', 'en'] : ['id'];
    }

    public function body(string $lang): ?string
    {
        return $lang === 'en' ? $this->body_en : $this->body_id;
    }

    public function title(string $lang): string
    {
        return ($lang === 'en' ? $this->title_en : null) ?: $this->title_id;
    }

    /** Ada perubahan yang belum ikut dipublikasikan. */
    public function hasUnpublishedChanges(): bool
    {
        return $this->is_published && $this->published_at && $this->updated_at && $this->updated_at->gt($this->published_at);
    }

    public function downloadName(string $lang): string
    {
        // "/" dan "\\" tidak boleh ada di nama file unduhan.
        $title = trim(preg_replace('/\s*[\/\\\\]\s*/', ' - ', $this->title($lang)));

        return 'GTR 2026 - ' . $title . ' (' . self::LANGS[$lang] . ').pdf';
    }
}
