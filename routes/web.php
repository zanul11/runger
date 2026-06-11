<?php

use App\Http\Controllers\PageController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\RunnerAuthController;
use App\Http\Controllers\RunnerDashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/agenda', [PageController::class, 'agenda'])->name('agenda');
Route::get('/agenda/{slug}', [PageController::class, 'eventDetail'])->name('event.detail');
Route::get('/galeri', [PageController::class, 'gallery'])->name('gallery');
Route::get('/gerung-trail-run', [PageController::class, 'gtr'])->name('gtr');
Route::get('/gerung-trail-run/entry-list', [PageController::class, 'gtrEntryList'])->name('gtr.entry');
Route::get('/gerung-trail-run/results', [PageController::class, 'gtrResults'])->name('gtr.results');
Route::get('/gerung-trail-run/rules', [PageController::class, 'gtrRules'])->name('gtr.rules');
Route::get('/gerung-trail-run/category/{slug}', [PageController::class, 'gtrCategory'])->name('gtr.category');

// Runger participant portal app (runner account system)
Route::middleware('guest:runner')->group(function () {
    Route::get('/runger-app/masuk', [RunnerAuthController::class, 'showLogin'])->name('gtr.login');
    Route::post('/runger-app/masuk', [RunnerAuthController::class, 'login'])->name('gtr.login.store');
    Route::get('/runger-app/daftar', [RunnerAuthController::class, 'showRegister'])->name('gtr.register');
    Route::post('/runger-app/daftar', [RunnerAuthController::class, 'register'])->name('gtr.register.store');
});
Route::middleware('auth:runner')->group(function () {
    Route::get('/runger-app', [RunnerDashboardController::class, 'index'])->name('gtr.dashboard');
    Route::get('/runger-app/race', [RunnerDashboardController::class, 'race'])->name('gtr.account.race');
    Route::get('/runger-app/race/{registration}', [RunnerDashboardController::class, 'raceDetail'])->name('gtr.account.race.detail');
    Route::get('/runger-app/transaksi', [RunnerDashboardController::class, 'transaction'])->name('gtr.account.transaction');
    Route::get('/runger-app/transaksi/selesai', [PaymentController::class, 'finish'])->name('gtr.payment.finish');
    Route::post('/runger-app/transaksi/{registration}/bayar', [PaymentController::class, 'pay'])->name('gtr.payment.pay');
    Route::get('/runger-app/profil', [RunnerDashboardController::class, 'profile'])->name('gtr.account.profile');
    Route::get('/runger-app/daftar/{category}', [RunnerDashboardController::class, 'registrationForm'])->name('gtr.register.form');
    Route::post('/runger-app/daftar/{category}', [RunnerDashboardController::class, 'storeRegistration'])->name('gtr.register.submit');
    Route::post('/runger-app/keluar', [RunnerAuthController::class, 'logout'])->name('gtr.logout');
});

// Midtrans payment notification / callback listener (webhook — no auth, no CSRF)
Route::post('/api/midtrans/notification', [PaymentController::class, 'notification'])->name('midtrans.notification');
Route::get('/volunteer-gtr', [PageController::class, 'volunteer'])->name('volunteer');
Route::post('/volunteer-gtr', [PageController::class, 'volunteerStore'])->name('volunteer.store');
