<?php

namespace App\Services;

use App\Casts\DateTimeMs;
use App\Models\GtrRegistration;
use App\Models\GtrScanLog;
use App\Models\GtrSetting;
use App\Models\GtrTimingPoint;
use App\Models\User;
use App\Support\Bib;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Menerima batch scan dari scanner PWA (POST /api/timing/scans/batch).
 *
 * Jaminan:
 *  - IDEMPOTEN: client_uuid unik. Kirim ulang uuid yang sama tidak membuat baris baru;
 *    jawabannya status baris yang sudah tersimpan.
 *  - APPEND-ONLY: scan tidak pernah dihapus. Scan yang tidak dihitung disimpan dengan
 *    is_ignored + ignore_reason (BIB tak terdaftar, salah kategori, duplikat, dibatalkan).
 *  - DEDUP DETERMINISTIK: scan BIB sama di pos sama < 60 detik dari scan sah sebelumnya
 *    diabaikan. Dihitung ulang per (peserta, pos) dari urutan WAKTU TERKOREKSI, bukan urutan
 *    datang, jadi hasilnya sama walau batch dari beberapa device tiba acak.
 *  - Waktu terkoreksi dihitung server: device_time_ms + clock_offset_ms.
 *
 * Item ditolak TANPA disimpan hanya bila bentuknya tidak valid, posnya bukan pos marshal
 * (tidak bisa dipercaya), atau waktunya sebelum reset data uji (gtr:reset-race-day). Semua jawaban final: scanner tidak perlu mengirim ulang.
 */
class GtrScanIngestor
{
    public const REJECT_INVALID = 'invalid';

    public const REJECT_WRONG_POST = 'wrong_post';

    /** Scan uji coba yang dibuat sebelum gtr:reset-race-day (antrean HP yang belum bersih). */
    public const REJECT_BEFORE_RESET = 'before_reset';

    /** Batas toleransi waktu terkoreksi di masa depan sebelum ditandai janggal. */
    private const FUTURE_TOLERANCE_MS = 120_000;

    /** @var list<int> kategori peserta yang tersentuh oleh ingest() terakhir */
    public array $touchedCategoryIds = [];

    /**
     * @param  array<int,mixed>  $items
     * @return array{accepted: list<string>, rejected: list<array{uuid:string,reason:string}>}
     */
    public function ingest(User $user, ?string $deviceId, array $items): array
    {
        $receivedAt = now();
        $resetAtMs = GtrSetting::timingResetAtMs();

        // Pos yang boleh dicatat user ini: semua pos yang pernah ditugaskan (aktif atau tidak),
        // supaya antrean offline dari pos lama tetap masuk setelah marshal dipindah.
        $pointIds = $user->timingPointAssignments()->pluck('gtr_timing_point_id')->unique()->all();
        $points = GtrTimingPoint::with('categories:id')->whereIn('id', $pointIds)->get()->keyBy('id');

        $bibMap = GtrRegistration::bibMap();
        $regCategory = GtrRegistration::whereIn('id', array_values($bibMap))->pluck('gtr_category_id', 'id');

        $uuids = collect($items)->map(fn ($i) => is_array($i) ? ($i['uuid'] ?? null) : null)
            ->filter(fn ($u) => is_string($u) && Str::isUuid($u))->unique()->values();
        $existing = GtrScanLog::whereIn('client_uuid', $uuids)->get()->keyBy('client_uuid');

        $results = [];      // uuid => reason|null (null = tunggu status final dari DB)
        $groups = [];       // "regId:tpId" => [regId, tpId] untuk dedup ulang

        foreach ($items as $item) {
            $uuid = is_array($item) && is_scalar($item['uuid'] ?? null) ? (string) $item['uuid'] : '';
            $error = $this->validate($item);

            if ($error !== null) {
                $results[$uuid] = self::REJECT_INVALID;

                continue;
            }

            $row = $existing->get($uuid);

            if (! $row) {
                $point = $points->get((int) $item['timing_point_id']);

                if (! $point) {
                    $results[$uuid] = self::REJECT_WRONG_POST;

                    continue;
                }

                if ($resetAtMs !== null && $item['device_time_ms'] + (int) ($item['clock_offset_ms'] ?? 0) < $resetAtMs) {
                    $results[$uuid] = self::REJECT_BEFORE_RESET;

                    continue;
                }

                $row = $this->store($user, $deviceId, $point, $item, $bibMap, $regCategory, $receivedAt);
                $existing->put($uuid, $row);
            } elseif (! empty($item['cancelled'])) {
                $this->cancel($row, $user);
            }

            $results[$uuid] = null;

            if ($row->gtr_registration_id) {
                $groups[$row->gtr_registration_id.':'.$row->gtr_timing_point_id] = [$row->gtr_registration_id, $row->gtr_timing_point_id];
            }
        }

        foreach ($groups as [$regId, $tpId]) {
            $this->refreshDuplicates($regId, $tpId);
        }

        // Kategori yang hasilnya perlu dihitung ulang (dipakai ScanBatchController).
        $this->touchedCategoryIds = GtrRegistration::whereIn('id', array_unique(array_column($groups, 0)))
            ->distinct()->pluck('gtr_category_id')->map(fn ($id) => (int) $id)->all();

        $final = GtrScanLog::whereIn('client_uuid', array_keys(array_filter($results, fn ($r) => $r === null)))
            ->get(['client_uuid', 'is_ignored', 'ignore_reason'])
            ->keyBy('client_uuid');

        $accepted = [];
        $rejected = [];
        foreach ($results as $uuid => $reason) {
            $uuid = (string) $uuid;
            if ($reason === null) {
                $row = $final->get($uuid);
                $reason = $row?->is_ignored ? ($row->ignore_reason ?: GtrScanLog::REASON_ADMIN) : null;
            }
            if ($reason === null) {
                $accepted[] = $uuid;
            } else {
                $rejected[] = ['uuid' => $uuid, 'reason' => $reason];
            }
        }

        return ['accepted' => $accepted, 'rejected' => $rejected];
    }

    /** null = valid; selain itu pesan error. */
    private function validate(mixed $item): ?string
    {
        if (! is_array($item)) {
            return 'item';
        }
        if (! is_string($item['uuid'] ?? null) || ! Str::isUuid($item['uuid'])) {
            return 'uuid';
        }
        if (! is_int($item['timing_point_id'] ?? null) && ! ctype_digit((string) ($item['timing_point_id'] ?? ''))) {
            return 'timing_point_id';
        }
        if (! is_int($item['device_time_ms'] ?? null) || $item['device_time_ms'] < 1_577_836_800_000) { // >= 2020
            return 'device_time_ms';
        }
        $offset = $item['clock_offset_ms'] ?? 0;
        if (! is_int($offset) || abs($offset) > 30 * 86_400_000) {
            return 'clock_offset_ms';
        }
        $source = $item['source'] ?? GtrScanLog::SOURCE_QR;
        if (! in_array($source, GtrScanLog::SCANNER_SOURCES, true)) {
            return 'source';
        }
        if (! is_string($item['bib'] ?? null) && ! is_int($item['bib'] ?? null)) {
            return 'bib';
        }
        if (isset($item['cancelled']) && ! is_bool($item['cancelled'])) {
            return 'cancelled';
        }

        return null;
    }

    /**
     * @param  array<string,int>  $bibMap
     */
    private function store(User $user, ?string $deviceId, GtrTimingPoint $point, array $item, array $bibMap, Collection $regCategory, $receivedAt): GtrScanLog
    {
        $bib = Bib::normalize($item['bib']);
        $regId = $bib !== null ? ($bibMap[$bib] ?? null) : null;
        $offset = (int) ($item['clock_offset_ms'] ?? 0);
        $correctedMs = $item['device_time_ms'] + $offset;

        $reason = match (true) {
            $bib === null => GtrScanLog::REASON_INVALID_BIB,
            $regId === null => GtrScanLog::REASON_UNKNOWN_BIB,
            ! $point->categories->contains('id', $regCategory[$regId] ?? null) => GtrScanLog::REASON_WRONG_CATEGORY,
            ! empty($item['cancelled']) => GtrScanLog::REASON_CANCELLED,
            default => null,
        };

        try {
            return GtrScanLog::create([
                'client_uuid' => $item['uuid'],
                'gtr_registration_id' => $regId,
                'gtr_timing_point_id' => $point->id,
                'bib_input' => mb_substr((string) $item['bib'], 0, 32),
                'scanned_by' => $user->id,
                'device_id' => $deviceId,
                'source' => $item['source'] ?? GtrScanLog::SOURCE_QR,
                'raw_device_time' => (int) $item['device_time_ms'],
                'clock_offset_ms' => $offset,
                'scanned_at' => $correctedMs,
                'server_received_at' => $receivedAt,
                // Waktu terkoreksi jauh di masa depan = jam device / offset janggal → cek admin.
                'is_flagged' => $correctedMs > $receivedAt->getTimestampMs() + self::FUTURE_TOLERANCE_MS,
                'is_ignored' => $reason !== null,
                'ignore_reason' => $reason,
                'ignored_by' => $reason === GtrScanLog::REASON_CANCELLED ? $user->id : null,
                'ignored_at' => $reason !== null ? $receivedAt : null,
            ]);
        } catch (UniqueConstraintViolationException) {
            // uuid sama masuk bersamaan dari request lain → pakai yang sudah tersimpan.
            return GtrScanLog::where('client_uuid', $item['uuid'])->firstOrFail();
        }
    }

    /** "Batalkan" dari marshal: tandai diabaikan (tidak dihapus). Hanya pemilik scan atau admin. */
    private function cancel(GtrScanLog $row, User $user): void
    {
        $owner = (int) $row->scanned_by === (int) $user->id || $user->isAdmin();
        $cancellable = ! $row->is_ignored || $row->ignore_reason === GtrScanLog::REASON_DUPLICATE;

        if ($owner && $cancellable) {
            $row->forceFill([
                'is_ignored' => true,
                'ignore_reason' => GtrScanLog::REASON_CANCELLED,
                'ignored_by' => $user->id,
                'ignored_at' => now(),
            ])->save();
        }
    }

    /**
     * Hitung ulang flag duplikat untuk satu (peserta, pos).
     *
     * Kandidat = scan yang sah atau sebelumnya ditandai duplikat (alasan lain — dibatalkan,
     * diabaikan admin, salah kategori — tidak disentuh). Urut waktu terkoreksi; scan pertama
     * sah, scan berikutnya sah hanya bila >= 60 detik dari scan sah terakhir.
     */
    public function refreshDuplicates(int $registrationId, int $timingPointId): void
    {
        DB::transaction(function () use ($registrationId, $timingPointId) {
            $rows = GtrScanLog::where('gtr_registration_id', $registrationId)
                ->where('gtr_timing_point_id', $timingPointId)
                ->where(fn ($q) => $q->where('is_ignored', false)->orWhere('ignore_reason', GtrScanLog::REASON_DUPLICATE))
                ->lockForUpdate()
                ->get();

            $keep = [];
            $lastKeptMs = null;
            foreach ($rows->sortBy([fn ($a, $b) => DateTimeMs::toMs($a->scanned_at) <=> DateTimeMs::toMs($b->scanned_at), ['id', 'asc']]) as $r) {
                $ms = DateTimeMs::toMs($r->scanned_at);
                if ($lastKeptMs === null || $ms - $lastKeptMs >= GtrScanLog::DEDUP_WINDOW_MS) {
                    $keep[$r->id] = true;
                    $lastKeptMs = $ms;
                }
            }

            foreach ($rows as $r) {
                $dup = ! isset($keep[$r->id]);
                if ($r->is_ignored !== $dup) {
                    $r->forceFill([
                        'is_ignored' => $dup,
                        'ignore_reason' => $dup ? GtrScanLog::REASON_DUPLICATE : null,
                        'ignored_at' => $dup ? now() : null,
                        'ignored_by' => null,
                    ])->save();
                }
            }
        });
    }
}
