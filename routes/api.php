<?php

use App\Http\Controllers\Api\Timing\AdminController;
use App\Http\Controllers\Api\Timing\ExpenseController;
use App\Http\Controllers\Api\Timing\PrepController;
use App\Http\Controllers\Api\Timing\RacepackController;
use App\Http\Controllers\Api\Timing\RosterController;
use App\Http\Controllers\Api\Timing\ScanBatchController;
use App\Http\Controllers\Api\Timing\SessionController;
use App\Http\Controllers\Api\Timing\TimeController;
use App\Http\Controllers\Api\Timing\WaveController;
use Illuminate\Support\Facades\Route;

// ===== Modul TIMING GTR (aplikasi panitia GTR Crew /crew) =====
// Role: marshal = scan timing · racepack = pengambilan racepack · admin = Kontrol + boleh scan
// timing (di pos yang ia pilih lewat /admin/location) & racepack · panitia = hanya Persiapan
// (session / time / prep; endpoint lain punya middleware role sendiri) · bendahara = Pengeluaran.
Route::prefix('timing')->name('timing.')->group(function () {
    // Login → token Sanctum per device.
    Route::post('/session', [SessionController::class, 'store'])->middleware('throttle:30,1')->name('session.store');

    Route::middleware(['auth:sanctum', 'api.role:marshal,racepack,admin,panitia,bendahara'])->group(function () {
        Route::get('/session', [SessionController::class, 'show'])->name('session.show');
        Route::delete('/session', [SessionController::class, 'destroy'])->name('session.destroy');
        Route::get('/time', TimeController::class)->name('time');

        Route::post('/scans/batch', ScanBatchController::class)->middleware('api.role:marshal,admin')->name('scans.batch');

        Route::middleware('api.role:racepack,admin')->group(function () {
            Route::get('/racepack/lookup', [RacepackController::class, 'lookup'])->name('racepack.lookup');
            Route::post('/racepack/checkin', [RacepackController::class, 'checkin'])->name('racepack.checkin');
            Route::get('/racepack/history', [RacepackController::class, 'history'])->name('racepack.history');
        });

        // Checklist persiapan panitia: semua akun GTR Crew boleh lihat; centang & kelola item hanya
        // role panitia (dan admin). Versi baca-saja publik: /crew/persiapan.
        Route::prefix('prep')->name('prep.')->controller(PrepController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::middleware('api.role:panitia,admin')->group(function () {
                Route::post('/{item}/check', 'check')->name('check');
                Route::post('/', 'store')->name('store');
                Route::post('/template', 'template')->name('template');
                Route::post('/category/rename', 'renameCategory')->name('category.rename');
                Route::post('/category/delete', 'deleteCategory')->name('category.delete');
                Route::put('/{item}', 'update')->name('update');
                Route::delete('/{item}', 'destroy')->name('destroy');
            });
        });

        // Roster panitia hari H: semua akun GTR Crew boleh lihat & export; isi petugas, tamu konsumsi
        // & import petugas: panitia (koordinator) + admin; import struktur: admin (dicek di controller).
        // Struktur pos dikelola di Filament. Versi baca-saja publik: /crew/roster.
        Route::prefix('roster')->name('roster.')->controller(RosterController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/export', 'export')->name('export');
            Route::middleware('api.role:panitia,admin')->group(function () {
                Route::put('/positions/{position}/slots/{slot}', 'slot')->whereNumber('slot')->name('slot');
                Route::put('/consumption', 'consumption')->name('consumption');
                Route::post('/import/preview', 'import')->name('import.preview');
                Route::post('/import', 'import')->name('import');
            });
            Route::patch('/{type}/{id}', 'update')->middleware('api.admin')
                ->whereIn('type', ['areas', 'groups', 'positions'])->whereNumber('id')->name('update');
            Route::delete('/{type}/{id}', 'destroy')->middleware('api.admin')
                ->whereIn('type', ['areas', 'groups', 'positions'])->whereNumber('id')->name('destroy');
        });

        // Pengeluaran panitia + kwitansi: bendahara & admin (lihat, tambah, ubah, hapus).
        Route::prefix('expenses')->name('expenses.')->controller(ExpenseController::class)
            ->middleware('api.role:bendahara,admin')->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/export', 'export')->name('export');
                Route::post('/', 'store')->name('store');
                // Multipart (kwitansi) tidak bisa lewat PUT di PHP → ubah memakai POST.
                Route::post('/{expense}', 'update')->name('update');
                Route::delete('/{expense}', 'destroy')->name('destroy');
                Route::get('/{expense}/receipt', 'receipt')->name('receipt');
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
