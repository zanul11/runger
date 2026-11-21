<?php

namespace App\Models;

use App\Services\RosterService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Pos / divisi roster panitia. Kebutuhan min–max petugas; slot input = max_petugas.
 * Pos nonaktif tidak dihitung & tidak tampil di roster, datanya (petugas) tetap disimpan.
 */
class RosterPosition extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'km' => 'array',
        'min_petugas' => 'integer',
        'max_petugas' => 'integer',
        'urutan' => 'integer',
        'aktif' => 'boolean',
    ];

    /**
     * Ikon pos: key => [label, path SVG 24×24 (gaya Lucide)]. Satu-satunya sumber ikon — dikirim
     * juga ke halaman roster lewat API, jadi menambah ikon cukup di sini.
     */
    public const ICONS = [
        'flag' => ['Bendera (start/finish)', ['M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z', 'M4 22v-7']],
        'medal' => ['Medali', ['M7.21 15 2.66 7.14a2 2 0 0 1 .13-2.2L4.4 2.8A2 2 0 0 1 6 2h12a2 2 0 0 1 1.6.8l1.6 2.14a2 2 0 0 1 .14 2.2L16.79 15', 'M11 12 5.12 2.2', 'm13 12 5.88-9.8', 'M8 7h8', 'M12 22a5 5 0 1 0 0-10 5 5 0 0 0 0 10z', 'M12 18v-2h-.5']],
        'aid' => ['Medis', ['M9 3h6v6h6v6h-6v6H9v-6H3V9h6z']],
        'water' => ['Air / refreshment', ['M12 22a7 7 0 0 0 7-7c0-2-1-3.9-3-5.5s-3.5-4-4-6.5c-.5 2.5-2 4.9-4 6.5C6 11.1 5 13 5 15a7 7 0 0 0 7 7z']],
        'radio' => ['Radio / komunikasi', ['M4.9 19.1C1 15.2 1 8.8 4.9 4.9', 'M7.8 16.2c-2.3-2.3-2.3-6.1 0-8.5', 'M12 14a2 2 0 1 0 0-4 2 2 0 0 0 0 4z', 'M16.2 7.8c2.3 2.3 2.3 6.1 0 8.5', 'M19.1 4.9C23 8.8 23 15.1 19.1 19']],
        'camera' => ['Kamera', ['M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z', 'M12 16a3 3 0 1 0 0-6 3 3 0 0 0 0 6z']],
        'food' => ['Konsumsi', ['M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2', 'M7 2v20', 'M21 15V2a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3zm0 0v7']],
        'watch' => ['Jam / timing', ['M12 18a6 6 0 1 0 0-12 6 6 0 0 0 0 12z', 'M12 10v2l1 1', 'm16.13 7.66-.81-4.05a2 2 0 0 0-2-1.61h-2.68a2 2 0 0 0-2 1.61l-.78 4.05', 'm7.88 16.36.8 4a2 2 0 0 0 2 1.61h2.72a2 2 0 0 0 2-1.61l.81-4.05']],
        'trophy' => ['Piala / podium', ['M6 9H4.5a2.5 2.5 0 0 1 0-5H6', 'M18 9h1.5a2.5 2.5 0 0 0 0-5H18', 'M4 22h16', 'M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22', 'M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22', 'M18 2H6v7a6 6 0 0 0 12 0V2z']],
        'info' => ['Info', ['M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20z', 'M12 16v-4', 'M12 8h.01']],
        'bag' => ['Tas / penitipan', ['M4 10a4 4 0 0 1 4-4h8a4 4 0 0 1 4 4v10a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2z', 'M8 10h8', 'M8 22v-6a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v6', 'M9 6V4a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2']],
        'mic' => ['Mikrofon / MC', ['M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3z', 'M19 10v2a7 7 0 0 1-14 0v-2', 'M12 19v3']],
        'shield' => ['Keamanan', ['M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z']],
        'box' => ['Logistik', ['M16.5 9.4 7.55 4.24', 'M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z', 'M3.27 6.96 12 12.01l8.73-5.05', 'M12 22.08V12']],
        'trash' => ['Kebersihan', ['M3 6h18', 'M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6', 'M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2']],
        'sign' => ['Penunjuk arah (marshal)', ['M12 13v8', 'M12 3v3', 'M18 6a2 2 0 0 1 1.39.56l2.3 2.22a1 1 0 0 1 0 1.44l-2.3 2.22A2 2 0 0 1 18 13H6a2 2 0 0 1-1.39-.56l-2.3-2.22a1 1 0 0 1 0-1.44l2.3-2.22A2 2 0 0 1 6 6z']],
        'scan' => ['Scan BIB', ['M3 7V5a2 2 0 0 1 2-2h2', 'M17 3h2a2 2 0 0 1 2 2v2', 'M21 17v2a2 2 0 0 1-2 2h-2', 'M7 21H5a2 2 0 0 1-2-2v-2', 'M7 12h10']],
        'clock' => ['Jam / cut-off', ['M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20z', 'M12 6v6l4 2']],
        'sweep' => ['Sweeper', ['m9.06 11.9 8.07-8.06a2.85 2.85 0 1 1 4.03 4.03l-8.06 8.08', 'M7.07 14.94c-1.66 0-3 1.35-3 3.02 0 1.33-2.5 1.52-2 2.02 1.08 1.1 2.49 2.02 4 2.02 2.2 0 4-1.8 4-4.04a3.01 3.01 0 0 0-3-3.02z']],
        'moto' => ['Motor / patroli', ['M5.5 21a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7z', 'M18.5 21a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7z', 'M15 6a1 1 0 1 0 0-2 1 1 0 0 0 0 2z', 'M12 17.5V14l-3-3 4-3 2 3h2']],
        'compass' => ['Kompas (komando)', ['M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20z', 'm16.24 7.76-1.8 5.41a2 2 0 0 1-1.27 1.27L7.76 16.24l1.8-5.41a2 2 0 0 1 1.27-1.27z']],
    ];

    public const DEFAULT_MARSHAL_DESC = 'Jaga titik, arahkan peserta sesuai marka rute, lapor ke Koordinator Lapangan.';

    protected static function booted(): void
    {
        // Max diturunkan → buang baris slot KOSONG di atas batas (slot terisi dicegah validasi).
        static::saved(function (RosterPosition $p) {
            if ($p->wasChanged('max_petugas')) {
                app(RosterService::class)->pruneEmptyAbove($p);
            }
        });
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(RosterGroup::class, 'group_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(RosterAssignment::class, 'position_id')->orderBy('slot_no');
    }

    /** Petugas terisi di slot di atas $max (akan hilang bila max diturunkan ke $max). */
    public function filledAbove(int $max)
    {
        return $this->assignments()->filled()->where('slot_no', '>', $max)->get();
    }

    /** SVG ikon (untuk preview di admin). */
    public static function iconSvg(?string $key, int $size = 18): string
    {
        $paths = self::ICONS[$key][1] ?? self::ICONS['flag'][1];
        $d = collect($paths)->map(fn ($p) => '<path d="'.e($p).'"/>')->join('');

        return '<svg width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;vertical-align:-3px">'.$d.'</svg>';
    }

    /** Opsi select ikon dengan preview (HTML). */
    public static function iconOptions(): array
    {
        return collect(self::ICONS)->mapWithKeys(fn ($v, $k) => [$k => self::iconSvg($k).'&nbsp; '.e($v[0])])->all();
    }

    /** Ikon untuk frontend: key => paths. */
    public static function iconPaths(): array
    {
        return collect(self::ICONS)->map(fn ($v) => $v[1])->all();
    }
}
