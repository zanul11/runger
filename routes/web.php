<?php

use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/agenda', [PageController::class, 'agenda'])->name('agenda');
Route::get('/agenda/{slug}', [PageController::class, 'eventDetail'])->name('event.detail');
Route::get('/galeri', [PageController::class, 'gallery'])->name('gallery');
