<?php

namespace App\Services;

use App\Models\Event;
use App\Models\RosterArea;
use App\Models\RosterAssignment;
use App\Models\RosterConsumptionSetting;
use App\Models\RosterGroup;
use App\Models\RosterPosition;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Roster panitia hari H: data untuk halaman roster, simpan slot petugas, duplikat struktur antar
 * event, serta export / import JSON. Total (kebutuhan, terisi, kekurangan, konsumsi) dihitung di
 * halaman roster dari data ini (resources/js/roster/logic.js).
 */
class RosterService
{
    public const EXPORT_FORMAT = 'gtr-roster';

    // ------------------------------------------------------------------ halaman roster

    /**
     * Data lengkap roster satu event (hanya pos aktif).
     *
     * @param  bool  $public  halaman publik tanpa login: no. HP disamarkan, tanpa nama pengubah.
     */
    public function payload(Event $event, ?User $user = null, bool $public = false): array
    {
        $areas = $this->loadTree($event, activeOnly: true);
        $setting = RosterConsumptionSetting::forEvent($event);
        $version = 0;

        $areaRows = $areas->map(function (RosterArea $a) use (&$version, $public) {
            $version = max($version, $a->updated_at?->getTimestampMs() ?? 0);

            return [
                'id' => $a->id,
                'nama' => $a->nama,
                'slug' => $a->slug,
                'groups' => $a->groups->map(function (RosterGroup $g) use (&$version, $public) {
                    $version = max($version, $g->updated_at?->getTimestampMs() ?? 0);

                    return [
                        'id' => $g->id,
                        'nama' => $g->nama,
                        'positions' => $g->positions->map(function (RosterPosition $p) use (&$version, $public) {
                            $version = max($version, $p->updated_at?->getTimestampMs() ?? 0);
                            $bySlot = $p->assignments->keyBy('slot_no');
                            foreach ($p->assignments as $as) {
                                $version = max($version, $as->updated_at?->getTimestampMs() ?? 0);
                            }

                            return [
                                'id' => $p->id,
                                'nama' => $p->nama,
                                'deskripsi' => $p->deskripsi,
                                'min' => $p->min_petugas,
                                'max' => $p->max_petugas,
                                'km' => array_values($p->km ?? []),
                                'ikon' => $p->ikon,
                                'catatan_tambahan' => $p->catatan_tambahan,
                                'slots' => collect(range(1, max(1, $p->max_petugas)))
                                    ->map(fn (int $n) => $this->slotRow($n, $bySlot->get($n), $public))->all(),
                            ];
                        })->values()->all(),
                    ];
                })->values()->all(),
            ];
        })->values()->all();

        $refArea = $setting->areaAcuan ?? $areas->firstWhere('slug', 'lapangan');

        return [
            'event' => $this->eventRow($event),
            'areas' => $areaRows,
            'consumption' => [
                'tamu_tambahan' => $setting->tamu_tambahan,
                'air_per_orang' => $setting->air_per_orang,
                'jadwal' => array_values($setting->jadwal ?? []),
                'catatan' => $setting->catatan,
                'area_acuan_id' => $refArea?->id,
                'area_acuan_nama' => $refArea?->nama,
            ],
            'icons' => RosterPosition::iconPaths(),
            'perms' => [
                'fill' => ! $public && $this->canFill($user),
                'structure' => ! $public && (bool) $user?->isAdmin(),
            ],
            'public' => $public,
            'version' => max($version, $setting->updated_at?->getTimestampMs() ?? 0),
        ];
    }

    /** Admin & panitia (koordinator) boleh mengisi petugas; role lain hanya melihat. */
    public function canFill(?User $user): bool
    {
        return in_array($user?->role, [User::ROLE_ADMIN, User::ROLE_PANITIA], true);
    }

    public function eventRow(Event $event): array
    {
        return [
            'id' => $event->id,
            'nama' => $event->title,
            'tanggal' => $event->date?->format('Y-m-d'),
            'tanggal_label' => $event->date?->locale('id')->translatedFormat('l, j F Y'),
            'lokasi_race_village' => $event->lokasi_race_village,
            'lokasi_rute' => $event->lokasi_rute ?: $event->location,
            'kategori' => $event->distance_text,
            'catatan_open_gate' => $event->catatan_open_gate,
            'penyelenggara' => $event->penyelenggara,
        ];
    }

    private function slotRow(int $n, ?RosterAssignment $a, bool $public): array
    {
        return [
            'slot_no' => $n,
            'nama' => (string) ($a?->nama ?? ''),
            'no_hp' => $public ? $this->maskPhone($a?->no_hp) : (string) ($a?->no_hp ?? ''),
            'catatan' => (string) ($a?->catatan ?? ''),
            'updated_by' => $public ? null : $a?->updatedBy?->name,
            'updated_at_ms' => $a?->updated_at?->getTimestampMs(),
        ];
    }

    /** 081234567890 → 0812•••890 (halaman publik tanpa login). */
    public function maskPhone(?string $phone): string
    {
        $p = trim((string) $phone);
        if ($p === '') {
            return '';
        }
        $len = mb_strlen($p);

        return $len <= 6 ? str_repeat('•', $len) : mb_substr($p, 0, 4).'•••'.mb_substr($p, -3);
    }

    /** Pohon area → grup → pos (+ petugas) satu event, terurut. */
    public function loadTree(Event $event, bool $activeOnly = false)
    {
        return $event->rosterAreas()->with([
            'groups.positions' => fn ($q) => $activeOnly ? $q->where('aktif', true) : $q,
            'groups.positions.assignments.updatedBy:id,name',
        ])->get();
    }

    // ------------------------------------------------------------------ isi slot

    /**
     * Simpan satu slot petugas (hanya field yang dikirim). Data terakhir menang.
     *
     * @param  array{nama?: ?string, no_hp?: ?string, catatan?: ?string}  $data
     */
    public function saveSlot(RosterPosition $position, int $slotNo, array $data, ?User $user): RosterAssignment
    {
        if (! $position->aktif) {
            throw ValidationException::withMessages(['slot' => 'Pos ini sedang nonaktif.']);
        }
        if ($slotNo < 1 || $slotNo > $position->max_petugas) {
            throw ValidationException::withMessages(['slot' => "Slot {$slotNo} di luar kebutuhan pos (maks. {$position->max_petugas})."]);
        }

        $fields = collect(Arr::only($data, ['nama', 'no_hp', 'catatan']))
            ->map(fn ($v) => ($v = trim((string) $v)) === '' ? null : $v)->all();

        $row = RosterAssignment::firstOrNew(['position_id' => $position->id, 'slot_no' => $slotNo]);
        $row->fill($fields);
        $row->updated_by = $user?->id;
        // Selalu sentuh updated_at supaya perangkat lain melihat perubahan terakhir.
        $row->updated_at = now();
        $row->save();

        return $row->load('updatedBy:id,name');
    }

    public function slotPayload(RosterAssignment $a): array
    {
        return $this->slotRow($a->slot_no, $a, false);
    }

    // ------------------------------------------------------------------ ubah kebutuhan

    /**
     * Cek penurunan max: petugas di slot > $newMax akan hilang dari roster.
     *
     * @return string|null pesan galat, null = aman
     */
    public function maxChangeError(RosterPosition $position, int $newMax): ?string
    {
        if (! $info = $this->maxChangeInfo($position, $newMax)) {
            return null;
        }
        $hint = $info['fits']
            ? 'Klik "Rapatkan slot" untuk memindahkan mereka ke slot kosong, atau batalkan perubahan.'
            : 'Slot kosong tidak cukup — kosongkan dulu sebagian petugas di halaman roster, atau batalkan perubahan.';

        return "Ada {$info['count']} petugas di slot yang akan dihapus (slot ".implode(', ', $info['slots']).'). '.$hint;
    }

    /**
     * Petugas di slot > $newMax (hilang bila maks diturunkan).
     *
     * @return array{count: int, slots: list<int>, fits: bool}|null fits = muat dipindah ke slot kosong
     */
    public function maxChangeInfo(RosterPosition $position, int $newMax): ?array
    {
        $above = $position->filledAbove($newMax);
        if ($above->isEmpty()) {
            return null;
        }

        return [
            'count' => $above->count(),
            'slots' => $above->pluck('slot_no')->all(),
            'fits' => count($this->emptySlotsUpTo($position, $newMax)) >= $above->count(),
        ];
    }

    /** Nomor slot kosong (≤ $max). */
    public function emptySlotsUpTo(RosterPosition $position, int $max): array
    {
        $filled = $position->assignments()->filled()->pluck('slot_no')->all();

        return array_values(array_diff(range(1, max(1, $max)), $filled));
    }

    /**
     * Rapatkan slot: petugas di slot > $max dipindah ke slot kosong ≤ $max (urut nomor).
     *
     * @return array{moved: int, left: int}
     */
    public function compactSlots(RosterPosition $position, int $max): array
    {
        return DB::transaction(function () use ($position, $max) {
            $above = $position->filledAbove($max);
            $empty = $this->emptySlotsUpTo($position, $max);
            $moved = 0;
            foreach ($above as $row) {
                $to = array_shift($empty);
                if ($to === null) {
                    break;
                }
                // Baris kosong di slot tujuan (nama kosong) dibuang dulu karena unik (position, slot).
                RosterAssignment::where('position_id', $position->id)->where('slot_no', $to)->delete();
                $row->forceFill(['slot_no' => $to])->save();
                $moved++;
            }

            return ['moved' => $moved, 'left' => $above->count() - $moved];
        });
    }

    /** Setelah max turun: buang baris KOSONG di atas batas (yang terisi dicegah lewat validasi). */
    public function pruneEmptyAbove(RosterPosition $position): void
    {
        RosterAssignment::where('position_id', $position->id)
            ->where('slot_no', '>', $position->max_petugas)
            ->where(fn ($q) => $q->whereNull('nama')->orWhere('nama', ''))
            ->delete();
    }

    // ------------------------------------------------------------------ duplikat struktur

    /**
     * Salin struktur (area, grup, pos) + setting konsumsi dari $from ke $to, tanpa nama petugas.
     *
     * @param  bool  $replace  hapus struktur $to yang sudah ada (beserta petugasnya) lebih dulu
     * @return int jumlah pos yang disalin
     */
    public function copyStructure(Event $from, Event $to, bool $replace = false): int
    {
        if ($from->is($to)) {
            throw ValidationException::withMessages(['source' => 'Pilih event lain sebagai sumber.']);
        }

        return DB::transaction(function () use ($from, $to, $replace) {
            if ($replace) {
                $to->rosterAreas()->delete();
            } elseif ($to->rosterAreas()->exists()) {
                throw ValidationException::withMessages(['source' => 'Event ini sudah punya struktur roster. Centang "Ganti struktur yang ada" untuk menimpanya.']);
            }

            $areaMap = [];
            $n = 0;
            $urutanBase = (int) $to->rosterAreas()->max('urutan');
            foreach ($this->loadTree($from) as $area) {
                $newArea = $to->rosterAreas()->create([
                    'nama' => $area->nama, 'slug' => $area->slug, 'urutan' => $urutanBase + $area->urutan,
                ]);
                $areaMap[$area->id] = $newArea->id;
                foreach ($area->groups as $group) {
                    $newGroup = $newArea->groups()->create(['nama' => $group->nama, 'urutan' => $group->urutan]);
                    foreach ($group->positions as $p) {
                        $newGroup->positions()->create(Arr::only($p->getAttributes(), [
                            'nama', 'deskripsi', 'min_petugas', 'max_petugas', 'ikon', 'catatan_tambahan', 'urutan', 'aktif',
                        ]) + ['km' => $p->km]);
                        $n++;
                    }
                }
            }

            if ($src = $from->rosterConsumption) {
                RosterConsumptionSetting::updateOrCreate(['event_id' => $to->id], [
                    'tamu_tambahan' => $src->tamu_tambahan,
                    'air_per_orang' => $src->air_per_orang,
                    'jadwal' => $src->jadwal,
                    'catatan' => $src->catatan,
                    'area_acuan_id' => $areaMap[$src->area_acuan_id] ?? null,
                ]);
            }

            return $n;
        });
    }

    // ------------------------------------------------------------------ export / import

    /** Export satu event: struktur (termasuk pos nonaktif) + petugas + setting konsumsi. */
    public function export(Event $event): array
    {
        $setting = RosterConsumptionSetting::forEvent($event);

        return [
            'format' => self::EXPORT_FORMAT,
            'version' => 1,
            'exported_at' => now()->toIso8601String(),
            'event' => $this->eventRow($event),
            'consumption' => [
                'tamu_tambahan' => $setting->tamu_tambahan,
                'air_per_orang' => $setting->air_per_orang,
                'jadwal' => array_values($setting->jadwal ?? []),
                'catatan' => $setting->catatan,
                'area_acuan_slug' => $setting->areaAcuan?->slug,
            ],
            'areas' => $this->loadTree($event)->map(fn (RosterArea $a) => [
                'id' => $a->id,
                'nama' => $a->nama,
                'slug' => $a->slug,
                'groups' => $a->groups->map(fn (RosterGroup $g) => [
                    'id' => $g->id,
                    'nama' => $g->nama,
                    'positions' => $g->positions->map(fn (RosterPosition $p) => [
                        'id' => $p->id,
                        'nama' => $p->nama,
                        'deskripsi' => $p->deskripsi,
                        'min_petugas' => $p->min_petugas,
                        'max_petugas' => $p->max_petugas,
                        'km' => array_values($p->km ?? []),
                        'ikon' => $p->ikon,
                        'catatan_tambahan' => $p->catatan_tambahan,
                        'aktif' => $p->aktif,
                        'slots' => $p->assignments->filter->isFilled()->map(fn (RosterAssignment $s) => [
                            'slot_no' => $s->slot_no,
                            'nama' => $s->nama,
                            'no_hp' => $s->no_hp,
                            'catatan' => $s->catatan,
                        ])->values()->all(),
                    ])->values()->all(),
                ])->values()->all(),
            ])->values()->all(),
        ];
    }

    /**
     * Import JSON hasil export.
     *  - mode "petugas": pos dicocokkan dari ID (di event ini) lalu nama; slot di file menimpa
     *    slot yang sama, slot lain tidak diubah. Struktur tidak berubah.
     *  - mode "struktur": struktur event disamakan dengan file (area/grup/pos baru dibuat, yang
     *    tidak ada di file dihapus) dan petugas tiap pos diganti isi file. Khusus admin.
     * $dryRun = preview: semua perubahan dijalankan di transaksi lalu dibatalkan.
     *
     * @return array{changes: list<array>, warnings: list<string>, summary: array<string,int>}
     */
    public function import(Event $event, array $data, string $mode, ?User $user, bool $dryRun = true): array
    {
        if (($data['format'] ?? null) !== self::EXPORT_FORMAT || ! is_array($data['areas'] ?? null)) {
            throw ValidationException::withMessages(['file' => 'File bukan export roster GTR (format tidak dikenal).']);
        }

        $log = new RosterImportLog;
        DB::beginTransaction();
        try {
            $mode === 'struktur'
                ? $this->importStructure($event, $data, $user, $log)
                : $this->importAssignments($event, $data, $user, $log);
            $dryRun ? DB::rollBack() : DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return $log->result();
    }

    private function importAssignments(Event $event, array $data, ?User $user, RosterImportLog $log): void
    {
        $tree = $this->loadTree($event);
        $positions = $tree->flatMap(fn ($a) => $a->groups->flatMap(fn ($g) => $g->positions->each(fn ($p) => $p->setRelation('group', $g->setRelation('area', $a)))));
        $byId = $positions->keyBy('id');
        $byName = $positions->groupBy(fn ($p) => $this->key($p->nama));

        foreach ($data['areas'] as $a) {
            foreach ($a['groups'] ?? [] as $g) {
                foreach ($g['positions'] ?? [] as $p) {
                    $label = trim(($a['nama'] ?? '?').' › '.($g['nama'] ?? '?').' › '.($p['nama'] ?? '?'));
                    $target = $this->matchPosition($p, $a, $g, $byId, $byName);
                    if (! $target) {
                        if (! empty($p['slots'])) {
                            $log->warn("Pos \"{$label}\" tidak ditemukan di event ini — dilewati.");
                        }

                        continue;
                    }
                    $current = $target->assignments->keyBy('slot_no');
                    foreach ($p['slots'] ?? [] as $s) {
                        $no = (int) ($s['slot_no'] ?? 0);
                        $where = $this->posLabel($target)." · slot {$no}";
                        if ($no < 1 || $no > $target->max_petugas) {
                            $log->warn("{$where}: melebihi kebutuhan pos (maks. {$target->max_petugas}) — dilewati.");

                            continue;
                        }
                        $this->applySlot($target, $no, $s, $current->get($no), $user, $log, $where);
                    }
                }
            }
        }
    }

    private function importStructure(Event $event, array $data, ?User $user, RosterImportLog $log): void
    {
        $existing = $this->loadTree($event);
        $keepAreas = [];
        $keepGroups = [];
        $keepPositions = [];
        $allPositions = $existing->flatMap(fn ($a) => $a->groups->flatMap->positions)->keyBy('id');

        foreach (array_values($data['areas']) as $ai => $a) {
            $nama = trim((string) ($a['nama'] ?? ''));
            if ($nama === '') {
                continue;
            }
            $slug = Str::slug($a['slug'] ?? '') ?: Str::slug($nama);
            $area = $existing->first(fn ($x) => $x->slug === $slug) ?? $existing->first(fn ($x) => $this->key($x->nama) === $this->key($nama));
            if (! $area) {
                $area = $event->rosterAreas()->create(['nama' => $nama, 'slug' => $slug, 'urutan' => $ai + 1]);
                $area->setRelation('groups', collect());
                $log->change('tambah', "Area {$nama}");
            } else {
                $area->fill(['nama' => $nama, 'urutan' => $ai + 1]);
                if ($area->isDirty('nama')) {
                    $log->change('ubah', "Area {$area->getOriginal('nama')}", $area->getOriginal('nama'), $nama);
                }
                $area->save();
            }
            $keepAreas[] = $area->id;

            foreach (array_values($a['groups'] ?? []) as $gi => $g) {
                $gNama = trim((string) ($g['nama'] ?? ''));
                if ($gNama === '') {
                    continue;
                }
                $group = $area->groups->first(fn ($x) => $this->key($x->nama) === $this->key($gNama));
                if (! $group) {
                    $group = $area->groups()->create(['nama' => $gNama, 'urutan' => $gi + 1]);
                    $group->setRelation('positions', collect());
                    $log->change('tambah', "Grup {$nama} › {$gNama}");
                } else {
                    $group->update(['urutan' => $gi + 1]);
                }
                $keepGroups[] = $group->id;

                foreach (array_values($g['positions'] ?? []) as $pi => $p) {
                    $pNama = trim((string) ($p['nama'] ?? ''));
                    if ($pNama === '') {
                        continue;
                    }
                    $min = max(0, (int) ($p['min_petugas'] ?? $p['min'] ?? 1));
                    $max = max(1, $min, (int) ($p['max_petugas'] ?? $p['max'] ?? 1));
                    $attrs = [
                        'group_id' => $group->id,
                        'nama' => $pNama,
                        'deskripsi' => $p['deskripsi'] ?? null,
                        'min_petugas' => $min,
                        'max_petugas' => $max,
                        'km' => array_values(array_filter(array_map('strval', (array) ($p['km'] ?? [])), 'strlen')) ?: null,
                        'ikon' => array_key_exists($p['ikon'] ?? '', RosterPosition::ICONS) ? $p['ikon'] : 'flag',
                        'catatan_tambahan' => $p['catatan_tambahan'] ?? null,
                        'urutan' => $pi + 1,
                        'aktif' => (bool) ($p['aktif'] ?? true),
                    ];
                    $label = "{$nama} › {$gNama} › {$pNama}";
                    $pos = (isset($p['id']) ? $allPositions->get($p['id']) : null)
                        ?? $group->positions->first(fn ($x) => $this->key($x->nama) === $this->key($pNama));
                    if ($pos && in_array($pos->id, $keepPositions, true)) {
                        $pos = null; // ID sama dipakai dua kali di file → buat pos baru
                    }
                    if (! $pos) {
                        $pos = RosterPosition::create($attrs);
                        $pos->setRelation('assignments', collect());
                        $log->change('tambah', "Pos {$label}", null, "{$min}–{$max} orang");
                    } else {
                        $before = "{$pos->min_petugas}–{$pos->max_petugas} orang".($pos->aktif ? '' : ' (nonaktif)');
                        $pos->fill($attrs);
                        if ($pos->isDirty(['nama', 'group_id', 'min_petugas', 'max_petugas', 'aktif', 'deskripsi', 'km', 'ikon', 'catatan_tambahan'])) {
                            $log->change('ubah', "Pos {$label}", $before, "{$min}–{$max} orang".($attrs['aktif'] ? '' : ' (nonaktif)'));
                        }
                        $pos->save();
                    }
                    $keepPositions[] = $pos->id;

                    // Petugas diganti isi file.
                    $current = $pos->assignments->keyBy('slot_no');
                    $fileSlots = collect($p['slots'] ?? [])->keyBy(fn ($s) => (int) ($s['slot_no'] ?? 0));
                    foreach ($fileSlots as $no => $s) {
                        if ($no < 1 || $no > $max) {
                            $log->warn("{$label} · slot {$no}: melebihi kebutuhan pos (maks. {$max}) — dilewati.");

                            continue;
                        }
                        $this->applySlot($pos, $no, $s, $current->get($no), $user, $log, "{$label} · slot {$no}");
                    }
                    foreach ($current as $no => $row) {
                        if (! $fileSlots->has($no) && $row->isFilled()) {
                            $log->change('kosongkan', "{$label} · slot {$no}", $this->who($row->nama, $row->no_hp), null);
                        }
                        if (! $fileSlots->has($no) || $no > $max) {
                            $row->delete();
                        }
                    }
                }
            }
        }

        // Yang tidak ada di file → dihapus (beserta petugasnya).
        foreach ($existing as $area) {
            foreach ($area->groups as $group) {
                foreach ($group->positions as $pos) {
                    if (! in_array($pos->id, $keepPositions, true)) {
                        $filled = $pos->assignments->filter->isFilled()->count();
                        $log->change('hapus', "Pos {$area->nama} › {$group->nama} › {$pos->nama}", $filled ? "{$filled} petugas" : null, null);
                        $pos->delete();
                    }
                }
                if (! in_array($group->id, $keepGroups, true)) {
                    $log->change('hapus', "Grup {$area->nama} › {$group->nama}");
                    $group->delete();
                }
            }
            if (! in_array($area->id, $keepAreas, true)) {
                $log->change('hapus', "Area {$area->nama}");
                $area->delete();
            }
        }

        if (is_array($c = $data['consumption'] ?? null)) {
            $setting = RosterConsumptionSetting::forEvent($event);
            $setting->fill([
                'tamu_tambahan' => max(0, (int) ($c['tamu_tambahan'] ?? $setting->tamu_tambahan)),
                'air_per_orang' => max(0, (int) ($c['air_per_orang'] ?? $setting->air_per_orang)),
                'jadwal' => collect($c['jadwal'] ?? $setting->jadwal ?? [])
                    ->map(fn ($r) => ['waktu' => (string) ($r['waktu'] ?? ''), 'kegiatan' => (string) ($r['kegiatan'] ?? '')])
                    ->filter(fn ($r) => $r['waktu'] !== '' || $r['kegiatan'] !== '')->values()->all(),
                'catatan' => $c['catatan'] ?? $setting->catatan,
                'area_acuan_id' => isset($c['area_acuan_slug'])
                    ? $event->rosterAreas()->where('slug', $c['area_acuan_slug'])->value('id')
                    : $setting->area_acuan_id,
            ]);
            if ($setting->isDirty()) {
                $log->change('ubah', 'Setting konsumsi');
            }
            $setting->save();
        }
    }

    private function applySlot(RosterPosition $pos, int $no, array $s, ?RosterAssignment $row, ?User $user, RosterImportLog $log, string $where): void
    {
        $new = ['nama' => $this->clean($s['nama'] ?? null), 'no_hp' => $this->clean($s['no_hp'] ?? null), 'catatan' => $this->clean($s['catatan'] ?? null)];
        $old = ['nama' => $this->clean($row?->nama), 'no_hp' => $this->clean($row?->no_hp), 'catatan' => $this->clean($row?->catatan)];
        if ($new === $old) {
            return;
        }
        $type = $old['nama'] === null ? 'tambah' : ($new['nama'] === null ? 'kosongkan' : 'ubah');
        $log->change($type, $where, $this->who($old['nama'], $old['no_hp']), $this->who($new['nama'], $new['no_hp']));
        RosterAssignment::updateOrCreate(['position_id' => $pos->id, 'slot_no' => $no], $new + ['updated_by' => $user?->id]);
    }

    private function matchPosition(array $p, array $a, array $g, $byId, $byName): ?RosterPosition
    {
        if (isset($p['id']) && ($hit = $byId->get($p['id'])) && $this->key($hit->nama) === $this->key($p['nama'] ?? '')) {
            return $hit;
        }
        $same = $byName->get($this->key($p['nama'] ?? '')) ?? collect();
        if ($same->count() > 1) {
            // Nama pos kembar di beberapa grup: pakai yang grup & areanya sama.
            $same = $same->filter(fn ($x) => $this->key($x->group->nama) === $this->key($g['nama'] ?? '')
                && $this->key($x->group->area->nama) === $this->key($a['nama'] ?? ''));
        }

        return $same->count() === 1 ? $same->first() : (isset($p['id']) ? $byId->get($p['id']) : null);
    }

    private function posLabel(RosterPosition $p): string
    {
        return $p->group->area->nama.' › '.$p->group->nama.' › '.$p->nama;
    }

    private function key(?string $s): string
    {
        return mb_strtolower(preg_replace('/\s+/', ' ', trim((string) $s)));
    }

    private function clean($v): ?string
    {
        $v = trim((string) $v);

        return $v === '' ? null : $v;
    }

    private function who(?string $nama, ?string $hp): ?string
    {
        return $nama === null ? null : ($hp ? "{$nama} ({$hp})" : $nama);
    }
}
