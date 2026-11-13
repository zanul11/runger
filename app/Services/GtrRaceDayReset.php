<?php

namespace App\Services;

use App\Casts\DateTimeMs;
use App\Http\Controllers\Api\Timing\SessionController;
use App\Http\Controllers\GtrBibCheckController;
use App\Http\Controllers\GtrResultCheckController;
use App\Models\GtrCategory;
use App\Models\GtrRegistration;
use App\Models\GtrResult;
use App\Models\GtrResultAdjustment;
use App\Models\GtrScanLog;
use App\Models\GtrSetting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Hapus data UJI COBA race day (dipakai `gtr:reset-race-day` dan menu Kontrol di scanner).
 *
 * Dihapus: semua scan BIB, hasil & koreksi hasil, gun time (START), status lomba peserta,
 * results_published dimatikan. Opsional: check-in racepack dan login HP scanner.
 * Tidak disentuh: peserta, pembayaran, BIB, kategori, pos & penugasan marshal, akun.
 *
 * Sebelum menghapus, data yang terkena disalin ke disk local: gtr-reset/<waktu>.json.
 * Jam reset dicatat di gtr_settings.timing_reset_at: server menolak scan / check-in racepack
 * dari antrean HP yang waktunya sebelum itu, dan scanner menghapus antrean lokal lamanya.
 */
class GtrRaceDayReset
{
    /**
     * @param  int|null  $keepTokenId  token yang tidak ikut di-logout (tidak dihitung)
     * @return array<string,int> label => jumlah baris yang akan terkena
     */
    public function counts(bool $racepack, bool $logout, ?int $keepTokenId = null): array
    {
        $counts = [
            'Scan BIB' => GtrScanLog::count(),
            'Hasil' => GtrResult::count(),
            'Koreksi hasil' => GtrResultAdjustment::count(),
            'Kategori sudah di-START' => GtrCategory::whereNotNull('gun_fired_at')->count(),
            'Status lomba peserta' => GtrRegistration::where('race_status', '!=', GtrRegistration::RACE_REGISTERED)->count(),
        ];
        if ($racepack) {
            $counts['Check-in racepack'] = GtrRegistration::whereNotNull('racepack_collected_at')->count();
        }
        if ($logout) {
            $counts['Login HP scanner'] = $this->timingTokens()->when($keepTokenId, fn ($q) => $q->whereKeyNot($keepTokenId))->count();
        }

        return $counts;
    }

    /**
     * @param  int|null  $keepTokenId  token yang TIDAK ikut di-logout (HP admin yang menjalankan reset)
     * @return array{backup: string, reset_at: Carbon}
     */
    public function run(bool $racepack, bool $logout, ?int $keepTokenId = null): array
    {
        $backup = $this->backup($racepack);
        $resetAt = now();

        DB::transaction(function () use ($racepack, $logout, $keepTokenId, $resetAt) {
            GtrResultAdjustment::query()->delete();
            GtrResult::query()->delete();
            GtrScanLog::query()->delete();

            GtrCategory::query()->whereNotNull('gun_fired_at')
                ->update(['gun_fired_at' => null, 'gun_fired_by' => null]);

            GtrRegistration::query()->where('race_status', '!=', GtrRegistration::RACE_REGISTERED)
                ->update(['race_status' => GtrRegistration::RACE_REGISTERED]);

            if ($racepack) {
                GtrRegistration::query()->whereNotNull('racepack_collected_at')
                    ->update(['racepack_collected_at' => null, 'racepack_collected_by' => null, 'racepack_picked_by' => null]);
            }

            if ($logout) {
                $this->timingTokens()->when($keepTokenId, fn ($q) => $q->whereKeyNot($keepTokenId))->delete();
            }

            // Tanpa event model: results_published=false tidak boleh memicu recompute.
            $setting = GtrSetting::first() ?? new GtrSetting;
            $setting->forceFill(['results_published' => false, 'timing_reset_at' => $resetAt]);
            $setting->saveQuietly();
        });

        Cache::forget('gtr:results:public');
        Cache::forget(GtrResultCheckController::CACHE_KEY);
        Cache::forget(GtrBibCheckController::CACHE_KEY);

        return ['backup' => $backup, 'reset_at' => $resetAt];
    }

    private function timingTokens()
    {
        return PersonalAccessToken::query()->where('name', 'like', SessionController::TOKEN_PREFIX.'%');
    }

    /** Salin data yang akan dihapus/diubah ke JSON (audit / pemulihan manual). @return string path di disk local */
    private function backup(bool $racepack): string
    {
        $path = 'gtr-reset/'.now()->format('Ymd-His-v').'.json';

        Storage::disk('local')->put($path, json_encode([
            'created_at' => now()->format(DateTimeMs::FORMAT),
            'scan_logs' => DB::table('gtr_scan_logs')->get(),
            'results' => DB::table('gtr_results')->get(),
            'result_adjustments' => DB::table('gtr_result_adjustments')->get(),
            'gun_fired' => DB::table('gtr_categories')->whereNotNull('gun_fired_at')->get(['id', 'name', 'gun_fired_at', 'gun_fired_by']),
            'race_status' => DB::table('gtr_registrations')->where('race_status', '!=', GtrRegistration::RACE_REGISTERED)->get(['id', 'race_status']),
            'racepack' => $racepack
                ? DB::table('gtr_registrations')->whereNotNull('racepack_collected_at')->get(['id', 'racepack_collected_at', 'racepack_collected_by', 'racepack_picked_by'])
                : [],
            'settings' => DB::table('gtr_settings')->get(['id', 'results_published', 'timing_reset_at']),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $path;
    }
}
