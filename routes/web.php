<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticatedSessionController as AuthController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;

Route::redirect('/dashboard', '/cms')->middleware(['auth','verified']);

Route::middleware('guest')->group(function () {
    Route::get('/', function () { return view('welcome');})->name('home');
    Route::get('login',    [AuthController::class,'create'])->name('login');
    Route::get('register', [RegisteredUserController::class,'create'])->name('register');
    Route::get('forgot-password', [PasswordResetLinkController::class,'create'])->name('password.request');
    Route::get('confirm-password', [ConfirmablePasswordController::class,'show'])->name('password.confirm');
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
