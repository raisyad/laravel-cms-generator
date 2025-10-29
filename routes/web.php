<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PostController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

/*
|-------------------------------------------
| CMS routes (selalu /cms + cms.*)
|-------------------------------------------
*/

// ---------------------------------------------
// CMS routes group (auto-managed)
// Jangan hapus marker START/END agar generator bisa inject resource
// [cms-generator] START
Route::middleware(['auth'])
    ->prefix('cms')
    ->as('cms.')
    ->group(function () {
        // [cms-generator] INSERT HERE
        Route::resource('posts', \App\Http\Controllers\PostController::class)->names('posts');
        Route::post('posts/{id}/restore', [\App\Http\Controllers\PostController::class, 'restore'])->name('posts.restore');
    });
// [cms-generator] END
