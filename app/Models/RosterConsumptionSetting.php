<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Setting konsumsi Race Village per event (tamu tambahan, air per orang, jadwal). */
class RosterConsumptionSetting extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'tamu_tambahan' => 'integer',
        'air_per_orang' => 'integer',
        'jadwal' => 'array',
    ];

    public const DEFAULT_CATATAN = 'Jadwal disesuaikan dengan jam start dan open gate.';

    /** Jadwal awal (bisa diubah admin). */
    public const DEFAULT_JADWAL = [
        ['waktu' => '04.00', 'kegiatan' => 'Sarapan panitia Race Village'],
        ['waktu' => '04.30', 'kegiatan' => 'Distribusi paket sarapan & air ke pos lapangan'],
        ['waktu' => '09.00', 'kegiatan' => 'Snack box panitia & tamu'],
        ['waktu' => '11.30', 'kegiatan' => 'Makan siang panitia, tamu/VIP & sponsor'],
    ];

    protected $attributes = [
        'tamu_tambahan' => 10,
        'air_per_orang' => 2,
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function areaAcuan(): BelongsTo
    {
        return $this->belongsTo(RosterArea::class, 'area_acuan_id');
    }

    /** Setting untuk event (dibuat dengan nilai default bila belum ada). */
    public static function forEvent(Event $event): self
    {
        return static::firstOrCreate(['event_id' => $event->id], [
            'jadwal' => self::DEFAULT_JADWAL,
            'catatan' => self::DEFAULT_CATATAN,
        ]);
    }
}
