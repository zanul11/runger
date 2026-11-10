<?php

namespace App\Support;

/**
 * Pengelompokan scan sah di SATU pos menjadi "lewat" (kunjungan).
 * HARUS identik dengan clusterPasses() di resources/js/scanner/logic.js.
 *
 * Scan pertama = lewat ke-1. Scan berikutnya menjadi lewat baru hanya bila berjarak
 * ≥ GAP_MS dari lewat sebelumnya (scan yang lebih dekat dianggap orang yang sama masih di pos).
 * Contoh 15K di Pertigaan: 07:20 (WS1) … 08:31 (WS2).
 */
final class Passes
{
    public const GAP_MS = 600_000; // 10 menit

    /**
     * @param  list<int>  $timesMs  waktu terkoreksi (urutan bebas)
     * @return list<int> waktu tiap lewat, urut
     */
    public static function cluster(array $timesMs, int $gapMs = self::GAP_MS): array
    {
        sort($timesMs);
        $passes = [];
        foreach ($timesMs as $t) {
            if ($passes === [] || $t - end($passes) >= $gapMs) {
                $passes[] = $t;
            }
        }

        return $passes;
    }

    /**
     * Kunjungan dari kolom pivot `visits` (JSON). Cadangan: satu kunjungan dengan kode pos.
     *
     * @return list<array{code: string, km: ?float, cutoff: ?string}>
     */
    public static function visits(mixed $pivotVisits, string $fallbackCode, mixed $km = null): array
    {
        $list = is_string($pivotVisits) ? json_decode($pivotVisits, true) : $pivotVisits;
        if (! is_array($list) || $list === []) {
            return [['code' => $fallbackCode, 'km' => $km !== null ? (float) $km : null, 'cutoff' => null]];
        }

        return array_values(array_map(fn ($v) => [
            'code' => (string) ($v['code'] ?? $fallbackCode),
            'km' => isset($v['km']) ? (float) $v['km'] : null,
            'cutoff' => $v['cutoff'] ?? null,
        ], $list));
    }
}
