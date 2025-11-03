<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\LguUserController;
use App\Http\Controllers\KioskController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/auth/login', [AuthController::class, 'login']);

// Protected routes (JWT required)
Route::middleware('auth:api')->group(function () {
    // Auth routes
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/refresh', [AuthController::class, 'refresh']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // LGU Users CRUD
    Route::apiResource('lgu-users', LguUserController::class);

    // Kiosks CRUD
    Route::apiResource('kiosks', KioskController::class);
});
