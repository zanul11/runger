<?php

namespace App\Services;

use App\Casts\DateTimeMs;
use App\Models\GtrCategory;
use App\Models\GtrRegistration;
use App\Models\GtrResult;
use App\Models\GtrResultAdjustment;
use App\Models\GtrScanLog;
use App\Models\GtrTimingPoint;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Aksi admin modul timing (dipakai Filament). Setiap perubahan langsung menghitung ulang
 * hasil kategori yang terdampak supaya admin melihat efeknya saat itu juga.
 */
class GtrTimingAdmin
{
    public function __construct(
        private ResultCalculator $calculator,
        private GtrScanIngestor $ingestor,
        private GtrGunStart $gun,
    ) {}

    /** Tembak / koreksi start kategori. @return bool false bila sudah ditembak & tidak force */
    public function fireGun(GtrCategory $category, User $by, ?Carbon $at = null, bool $force = false): bool
    {
        $res = $this->gun->fire($category, $by, $at, $force);
        if ($res['fired']) {
            $this->calculator->recomputeCategory($category->id);
        }

        return $res['fired'];
    }

    /** Start bersama semua kategori (satu gun time). @return bool false bila sudah ditembak & tidak force */
    public function fireGunAll(User $by, ?Carbon $at = null, bool $force = false): bool
    {
        $res = $this->gun->fireAll($by, $at, $force);
        if ($res['fired']) {
            $this->calculator->recomputeAll();
        }

        return $res['fired'];
    }

    /** Abaikan scan (tidak dihapus). */
    public function ignoreScan(GtrScanLog $scan, User $by): void
    {
        $scan->forceFill([
            'is_ignored' => true,
            'ignore_reason' => GtrScanLog::REASON_ADMIN,
            'ignored_by' => $by->id,
            'ignored_at' => now(),
        ])->save();

        $this->refresh($scan);
    }

    /** Pulihkan scan yang diabaikan (admin / dibatalkan / salah kategori). Dedup dihitung ulang. */
    public function restoreScan(GtrScanLog $scan): void
    {
        if (! $scan->gtr_registration_id) {
            throw new InvalidArgumentException('Scan ini tidak terhubung ke peserta. Pakai "Perbaiki BIB".');
        }

        $scan->forceFill(['is_ignored' => false, 'ignore_reason' => null, 'ignored_by' => null, 'ignored_at' => null])->save();
        $this->refresh($scan);
    }

    /**
     * Perbaiki BIB salah ketik: hubungkan scan ke peserta yang benar lalu pulihkan.
     * BIB harus terdaftar (lunas) dan kategorinya melewati pos scan tersebut.
     */
    public function relinkScan(GtrScanLog $scan, string $bib): GtrRegistration
    {
        $reg = GtrRegistration::findByBib($bib);
        if (! $reg) {
            throw new InvalidArgumentException("BIB {$bib} tidak terdaftar / belum lunas.");
        }
        if (! $scan->timingPoint->categories()->whereKey($reg->gtr_category_id)->exists()) {
            throw new InvalidArgumentException("Kategori BIB {$bib} tidak melewati pos {$scan->timingPoint->code}.");
        }

        $oldReg = $scan->gtr_registration_id;
        $scan->forceFill([
            'gtr_registration_id' => $reg->id,
            'is_ignored' => false,
            'ignore_reason' => null,
            'ignored_by' => null,
            'ignored_at' => null,
        ])->save();

        if ($oldReg && $oldReg !== $reg->id) {
            $this->ingestor->refreshDuplicates($oldReg, $scan->gtr_timing_point_id);
            $this->calculator->recomputeCategory((int) GtrRegistration::whereKey($oldReg)->value('gtr_category_id'));
        }
        $this->refresh($scan);

        return $reg;
    }

    /**
     * Tambah scan oleh admin: peserta terbukti lewat pos tapi tidak ter-scan (HP mati, terlewat).
     * Ditandai source=admin & device_id=admin; dedup (< 60 detik) dan hasil dihitung ulang.
     */
    public function addManualScan(GtrTimingPoint $point, string $bib, Carbon $at, User $by): GtrScanLog
    {
        $reg = GtrRegistration::findByBib($bib);
        if (! $reg) {
            throw new InvalidArgumentException("BIB {$bib} tidak terdaftar / belum lunas.");
        }
        if (! $point->categories()->whereKey($reg->gtr_category_id)->exists()) {
            throw new InvalidArgumentException("Kategori BIB {$bib} tidak melewati pos {$point->code}.");
        }

        $scan = GtrScanLog::create([
            'client_uuid' => (string) Str::uuid(),
            'gtr_registration_id' => $reg->id,
            'gtr_timing_point_id' => $point->id,
            'bib_input' => mb_substr($bib, 0, 32),
            'scanned_by' => $by->id,
            'device_id' => 'admin',
            'source' => GtrScanLog::SOURCE_ADMIN,
            'raw_device_time' => $at,
            'clock_offset_ms' => 0,
            'scanned_at' => $at,
            'server_received_at' => now(),
            'is_flagged' => false,
            'is_ignored' => false,
        ]);
        $this->refresh($scan);

        return $scan->fresh();
    }

    /**
     * Koreksi manual hasil → tercatat di gtr_result_adjustments dan diterapkan ulang tiap hitung ulang.
     *
     * @param  string  $field  status | start_time | finish_time
     * @param  string|null  $value  status: finisher|dnf|dq|dns; waktu: "Y-m-d H:i:s(.v)" WITA, kosong = hapus waktu
     */
    public function adjustResult(GtrRegistration $reg, string $field, ?string $value, string $reason, User $by): GtrResultAdjustment
    {
        $value = $value === null ? null : trim($value);

        if ($field === GtrResultAdjustment::FIELD_STATUS) {
            $allowed = [GtrResult::STATUS_FINISHER, GtrResult::STATUS_DNF, GtrResult::STATUS_DQ, GtrResult::STATUS_DNS];
            if (! in_array($value, $allowed, true)) {
                throw new InvalidArgumentException('Status tidak valid.');
            }
        } elseif (in_array($field, [GtrResultAdjustment::FIELD_START_TIME, GtrResultAdjustment::FIELD_FINISH_TIME], true)) {
            if ($value !== null && $value !== '') {
                try {
                    $value = DateTimeMs::toCarbon($value)->format(DateTimeMs::FORMAT);
                } catch (\Throwable) {
                    throw new InvalidArgumentException('Format waktu: 2026-11-29 07:45:12.345 (WITA).');
                }
            } else {
                $value = null;
            }
        } else {
            throw new InvalidArgumentException('Field koreksi tidak dikenal.');
        }
        if (trim($reason) === '') {
            throw new InvalidArgumentException('Alasan koreksi wajib diisi.');
        }

        $result = $reg->result;
        $old = match ($field) {
            GtrResultAdjustment::FIELD_STATUS => $result?->status,
            GtrResultAdjustment::FIELD_START_TIME => $result?->start_time?->format(DateTimeMs::FORMAT),
            GtrResultAdjustment::FIELD_FINISH_TIME => $result?->finish_time?->format(DateTimeMs::FORMAT),
        };

        $adj = DB::transaction(fn () => GtrResultAdjustment::create([
            'gtr_registration_id' => $reg->id,
            'user_id' => $by->id,
            'field' => $field,
            'old_value' => $old,
            'new_value' => $value,
            'reason' => trim($reason),
        ]));

        $this->calculator->recomputeCategory($reg->gtr_category_id);

        return $adj;
    }

    /** Nonaktifkan koreksi (audit tetap ada) lalu hitung ulang. */
    public function deactivateAdjustment(GtrResultAdjustment $adj): void
    {
        $adj->update(['is_active' => false]);
        $this->calculator->recomputeCategory((int) $adj->registration->gtr_category_id);
    }

    private function refresh(GtrScanLog $scan): void
    {
        if ($scan->gtr_registration_id) {
            $this->ingestor->refreshDuplicates($scan->gtr_registration_id, $scan->gtr_timing_point_id);
            $this->calculator->recomputeCategory((int) GtrRegistration::whereKey($scan->gtr_registration_id)->value('gtr_category_id'));
        }
    }
}
