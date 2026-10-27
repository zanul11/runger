<?php

use App\Http\Controllers\GtrRaceGuideController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\RunnerAuthController;
use App\Http\Controllers\RunnerPasswordController;
use App\Http\Controllers\RunnerDashboardController;
use Illuminate\Support\Facades\Route;

// Formulir pendaftaran offline (blank, untuk dicetak) — admin only.
Route::get('/gtr/formulir-pendaftaran', [PageController::class, 'gtrRegistrationForm'])
    ->middleware('auth')
    ->name('gtr.registration-form');

// Laporan pembayaran versi cetak — admin only.
Route::get('/gtr/laporan-pembayaran', [PageController::class, 'gtrPaymentReport'])
    ->middleware('auth')
    ->name('gtr.payment-report');

// Pratinjau format email (pendaftaran / pembayaran) — admin only.
Route::get('/gtr/email-preview', [PageController::class, 'gtrEmailPreview'])
    ->middleware('auth')
    ->name('gtr.email-preview');

// Preview course map kategori GTR + export gambar resolusi tinggi — admin only.
Route::get('/gtr/course-preview/{category}', [PageController::class, 'gtrCoursePreview'])
    ->middleware('auth')
    ->name('gtr.course-preview');

// Generator Race Guide (slide carousel 1080×1350) dari konten admin + export & publikasi — admin only.
Route::middleware('auth')->prefix('gtr/race-guide')->name('gtr.race-guide')->group(function () {
    Route::get('/', [GtrRaceGuideController::class, 'generator']);
    Route::post('/upload', [GtrRaceGuideController::class, 'upload'])->name('.upload');
    Route::post('/publish', [GtrRaceGuideController::class, 'publish'])->name('.publish');
    Route::post('/unpublish', [GtrRaceGuideController::class, 'unpublish'])->name('.unpublish');
});

// Swagger UI dokumentasi API (dilayani via route agar tidak 404 di /docs).
Route::get('/docs', fn () => response()->file(public_path('docs/index.html')))->name('api.docs');
Route::get('/docs/openapi.yaml', fn () => response()->file(public_path('docs/openapi.yaml'), [
    'Content-Type' => 'application/yaml',
]));

Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/agenda', [PageController::class, 'agenda'])->name('agenda');
Route::get('/agenda/{slug}', [PageController::class, 'eventDetail'])->name('event.detail');
Route::get('/galeri', [PageController::class, 'gallery'])->name('gallery');
Route::get('/gerung-trail-run', [PageController::class, 'gtr'])->name('gtr');
Route::get('/gerung-trail-run/entry-list', [PageController::class, 'gtrEntryList'])->name('gtr.entry');
Route::get('/gerung-trail-run/results', [PageController::class, 'gtrResults'])->name('gtr.results');
Route::get('/gerung-trail-run/results/{bib}', [PageController::class, 'gtrResult'])->name('gtr.result');
Route::get('/gerung-trail-run/rules', [PageController::class, 'gtrRules'])->name('gtr.rules');
Route::get('/gerung-trail-run/race-guide', [GtrRaceGuideController::class, 'index'])->name('gtr.guide');
Route::get('/gerung-trail-run/race-guide/download', [GtrRaceGuideController::class, 'download'])->name('gtr.guide.zip');
Route::get('/gerung-trail-run/category/{slug}', [PageController::class, 'gtrCategory'])->name('gtr.category');
Route::get('/gerung-trail-run/category/{slug}/gpx', [PageController::class, 'gtrCategoryGpx'])->name('gtr.category.gpx');
Route::get('/gerung-trail-run/lacak', [PageController::class, 'gtrTrackSearch'])->name('gtr.track.search');
Route::get('/gerung-trail-run/lacak/{bib}', [PageController::class, 'gtrTrack'])->name('gtr.track');
Route::get('/gerung-trail-run/lacak/{bib}/data', [PageController::class, 'gtrTrackData'])->middleware('throttle:60,1')->name('gtr.track.data');

// Runger participant portal app (runner account system)
Route::middleware('guest:runner')->group(function () {
    Route::get('/runger-app/masuk', [RunnerAuthController::class, 'showLogin'])->name('gtr.login');
    Route::post('/runger-app/masuk', [RunnerAuthController::class, 'login'])->name('gtr.login.store');
    Route::get('/runger-app/daftar', [RunnerAuthController::class, 'showRegister'])->name('gtr.register');
    Route::post('/runger-app/daftar', [RunnerAuthController::class, 'register'])->name('gtr.register.store');
    Route::get('/runger-app/lupa-password', [RunnerPasswordController::class, 'showForgot'])->name('gtr.password.request');
    Route::post('/runger-app/lupa-password', [RunnerPasswordController::class, 'sendResetLink'])->middleware('throttle:5,1')->name('gtr.password.email');
    Route::get('/runger-app/reset-password/{token}', [RunnerPasswordController::class, 'showReset'])->name('gtr.password.reset');
    Route::post('/runger-app/reset-password', [RunnerPasswordController::class, 'reset'])->middleware('throttle:5,1')->name('gtr.password.update');
});
Route::middleware('auth:runner')->group(function () {
    Route::get('/runger-app', [RunnerDashboardController::class, 'index'])->name('gtr.dashboard');
    Route::get('/runger-app/race', [RunnerDashboardController::class, 'race'])->name('gtr.account.race');
    Route::get('/runger-app/race/{registration}', [RunnerDashboardController::class, 'raceDetail'])->name('gtr.account.race.detail');
    Route::post('/runger-app/race/{registration}/plan', [RunnerDashboardController::class, 'saveRacePlan'])->name('gtr.account.race.plan');
    Route::get('/runger-app/race/{registration}/pace-band', [RunnerDashboardController::class, 'paceBand'])->name('gtr.account.race.paceband');
    Route::get('/runger-app/race/{registration}/foto', [RunnerDashboardController::class, 'photos'])->name('gtr.account.race.photos');
    Route::get('/runger-app/transaksi', [RunnerDashboardController::class, 'transaction'])->name('gtr.account.transaction');
    Route::get('/runger-app/transaksi/selesai', [PaymentController::class, 'finish'])->name('gtr.payment.finish');
    Route::post('/runger-app/transaksi/{registration}/bayar', [PaymentController::class, 'pay'])->name('gtr.payment.pay');
    Route::get('/runger-app/profil', [RunnerDashboardController::class, 'profile'])->name('gtr.account.profile');
    Route::put('/runger-app/profil/password', [RunnerPasswordController::class, 'update'])->middleware('throttle:5,1')->name('gtr.account.password');
    Route::get('/runger-app/kategori/{category}', [RunnerDashboardController::class, 'category'])->name('gtr.account.category');
    Route::get('/runger-app/daftar/{category}', [RunnerDashboardController::class, 'registrationForm'])->name('gtr.register.form');
    Route::post('/runger-app/daftar/{category}', [RunnerDashboardController::class, 'storeRegistration'])->name('gtr.register.submit');
    Route::post('/runger-app/keluar', [RunnerAuthController::class, 'logout'])->name('gtr.logout');
});

// Midtrans payment notification / callback listener (webhook — no auth, no CSRF)
Route::post('/api/midtrans/notification', [PaymentController::class, 'notification'])->name('midtrans.notification');
Route::get('/volunteer-gtr', [PageController::class, 'volunteer'])->name('volunteer');
Route::post('/volunteer-gtr', [PageController::class, 'volunteerStore'])->name('volunteer.store');
