<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\LguUserController;
use App\Http\Controllers\KioskController;
use Illuminate\Http\Request;

// Public routes
Route::post('/auth/login', [AuthController::class, 'login']);

// Protected routes (JWT required)
Route::middleware('auth:api')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/refresh', [AuthController::class, 'refresh']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    
    // LGU Users CRUD routes
    Route::get('/lgu-users', [LguUserController::class, 'index']);
    Route::post('/lgu-users', [LguUserController::class, 'store']);
    Route::get('/lgu-users/{id}', [LguUserController::class, 'show']);
    Route::post('/lgu-users/{id}', [LguUserController::class, 'update']);
    Route::delete('/lgu-users/{id}', [LguUserController::class, 'destroy']);

    // Kiosk CRUD routes
    Route::get('/kiosks', [KioskController::class, 'index']);
    Route::post('/kiosks', [KioskController::class, 'store']);
    Route::get('/kiosks/{id}', [KioskController::class, 'show']);
    Route::post('/kiosks/{id}', [KioskController::class, 'update']);
    Route::delete('/kiosks/{id}', [KioskController::class, 'destroy']);
});
