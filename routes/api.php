<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\LguUserController;
use App\Http\Controllers\KioskUserController;
use Illuminate\Http\Request;

// Public routes
Route::post('/auth/login', [AuthController::class, 'login']);

// Password change route (protected)
Route::post('/auth/change-password', [ChangePasswordController::class, 'changePassword'])
    ->middleware('auth:sanctum');

// LGU Users CRUD routes (protected by authentication)
Route::middleware('auth:sanctum')->group(function () {
// Protected routes (JWT required)
Route::middleware('auth:api')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/refresh', [AuthController::class, 'refresh']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    
    // LGU Users CRUD routes
    Route::get('/lgu-users', [LguUserController::class, 'index']);
    Route::post('/lgu-users', [LguUserController::class, 'store']);
    Route::get('/lgu-users/{id}', [LguUserController::class, 'show']);
    Route::put('/lgu-users/{id}', [LguUserController::class, 'update']);
    Route::delete('/lgu-users/{id}', [LguUserController::class, 'destroy']);

    // Kiosk Users CRUD routes
    Route::get('/kiosk-users', [KioskUserController::class, 'index']);
    Route::post('/kiosk-users', [KioskUserController::class, 'store']);
    Route::get('/kiosk-users/{id}', [KioskUserController::class, 'show']);
    Route::put('/kiosk-users/{id}', [KioskUserController::class, 'update']);
    Route::delete('/kiosk-users/{id}', [KioskUserController::class, 'destroy']);
});
