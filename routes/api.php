<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MarshalController;
use App\Http\Controllers\Api\MeController;
use App\Http\Controllers\Api\RosterController;
use App\Http\Controllers\Api\ScanController;
use App\Http\Controllers\Api\ServerTimeController;
use Illuminate\Support\Facades\Route;

// ===== Modul RACE-TIMING GTR =====

// Publik: untuk koreksi clock device & login marshal.
Route::get('/server-time', ServerTimeController::class);
Route::post('/login', [AuthController::class, 'login']);

// Butuh bearer token Sanctum (per device/marshal).
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', MeController::class);
    Route::get('/events/{event}/roster', RosterController::class);
    Route::post('/scans', [ScanController::class, 'store']);

    // Admin-only.
    Route::middleware('api.admin')->group(function () {
        Route::post('/marshals', [MarshalController::class, 'store']);
        Route::put('/marshals/{marshal}/reassign', [MarshalController::class, 'reassign']);
    });
});
