<?php

use App\Http\Controllers\Api\Timing\AdminController;
use App\Http\Controllers\Api\Timing\RacepackController;
use App\Http\Controllers\Api\Timing\ScanBatchController;
use App\Http\Controllers\Api\Timing\SessionController;
use App\Http\Controllers\Api\Timing\TimeController;
use App\Http\Controllers\Api\Timing\WaveController;
use Illuminate\Support\Facades\Route;

// ===== Modul TIMING GTR (scanner PWA /scanner) =====
// Role: marshal = scan timing · racepack = pengambilan racepack · admin = Kontrol + boleh scan
// timing (di pos yang ia pilih lewat /admin/location) & racepack.
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

        // Menu "Kontrol" scanner (admin): race day dari HP.
        Route::middleware('api.admin')->prefix('admin')->name('admin.')->controller(AdminController::class)->group(function () {
            Route::get('/race', 'race')->name('race');
            Route::get('/checklist', 'checklist')->name('checklist');
            Route::post('/location', 'location')->name('location');
            Route::post('/participants/{bib}/status', 'setStatus')->name('participant.status');
            Route::post('/start', 'start')->name('start');
            Route::post('/start/correct', 'correct')->name('start.correct');
            Route::get('/posts', 'posts')->name('posts');
            Route::get('/sweep', 'sweep')->name('sweep');
            Route::get('/participants', 'search')->name('participants');
            Route::get('/participants/{bib}', 'participant')->name('participant');
            Route::get('/finish', 'finish')->name('finish');
            Route::get('/points/{point}/scans', 'pointScans')->name('points.scans');
            Route::post('/points/{point}/scans', 'addScan')->name('points.scans.add');
            Route::get('/issues', 'issues')->name('issues');
            Route::post('/scans/{scan}/{action}', 'scanAction')->whereIn('action', ['ignore', 'restore', 'relink'])->name('scans.action');
            Route::get('/reset', 'resetPreview')->name('reset.preview');
            Route::post('/reset', 'reset')->middleware('throttle:5,1')->name('reset');
        });
    });
});
