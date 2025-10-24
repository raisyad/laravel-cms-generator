<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->apiResource('posts', \App\Http\Controllers\Api\PostApiController::class);
