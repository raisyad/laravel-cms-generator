<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController as AuthController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/cms', function () {
    return view('cms.dashboard');
})->middleware(['auth', 'verified'])->name('cms.dashboard');

Route::middleware('guest')->group(function () {
    Route::get('/', function () {
        return view('welcome');
    })->name('home');
    Route::get('login', [AuthController::class, 'create'])->name('login');
    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])->name('password.confirm');
    // dst...
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
|-------------------------------------------
| CMS routes (selalu /cms + cms.*)
|-------------------------------------------
*/

require __DIR__.'/auth.php';

// ---------------------------------------------
// CMS routes group (auto-managed)
// Jangan hapus marker START/END agar generator bisa inject resource
// [cms-generator] START
Route::middleware(['auth'])
    ->prefix('cms')
    ->as('cms.')
    ->group(function () {
        Route::get('/', function () {
            return view('cms.dashboard');
        })->name('dashboard');
        // [cms-generator] INSERT HERE
    });
// [cms-generator] END
