<?php

use App\Http\Controllers\Api\Timing\RacepackController;
use App\Http\Controllers\Api\Timing\ScanBatchController;
use App\Http\Controllers\Api\Timing\SessionController;
use App\Http\Controllers\Api\Timing\TimeController;
use App\Http\Controllers\Api\Timing\WaveController;
use Illuminate\Support\Facades\Route;

// ===== Modul TIMING GTR (scanner PWA /scanner) =====
// Role: marshal = timing · racepack = pengambilan racepack · admin = semuanya.
Route::prefix('timing')->name('timing.')->group(function () {
    // Login → token Sanctum per device.
    Route::post('/session', [SessionController::class, 'store'])->middleware('throttle:30,1')->name('session.store');

    Route::middleware(['auth:sanctum', 'api.role:marshal,racepack,admin'])->group(function () {
        Route::get('/session', [SessionController::class, 'show'])->name('session.show');
        Route::delete('/session', [SessionController::class, 'destroy'])->name('session.destroy');
        Route::get('/time', TimeController::class)->name('time');

        Route::post('/scans/batch', ScanBatchController::class)->middleware('api.role:marshal,admin')->name('scans.batch');

        Route::middleware('api.role:racepack,admin')->group(function () {
            Route::get('/racepack/lookup', [RacepackController::class, 'lookup'])->name('racepack.lookup');
            Route::post('/racepack/checkin', [RacepackController::class, 'checkin'])->name('racepack.checkin');
        });

        // Tembak start kategori: admin saja.
        Route::post('/waves/{category}/start', [WaveController::class, 'start'])->middleware('api.admin')->name('waves.start');
    });
});
