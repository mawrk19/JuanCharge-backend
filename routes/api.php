<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\LguUserController;
use App\Http\Controllers\KioskController;
use App\Http\Controllers\KioskUserController;
use App\Http\Controllers\ChangePasswordController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/auth/login', [AuthController::class, 'login']);

// Protected routes (require Sanctum authentication)
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/refresh', [AuthController::class, 'refresh']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // LGU Users CRUD
    Route::get('/lgu-users', [LguUserController::class, 'index']);
    Route::post('/lgu-users', [LguUserController::class, 'store']);
    Route::get('/lgu-users/{id}', [LguUserController::class, 'show']);
    Route::put('/lgu-users/{id}', [LguUserController::class, 'update']);
    Route::delete('/lgu-users/{id}', [LguUserController::class, 'destroy']);
    Route::patch('/lgu-users/{id}/disable', [LguUserController::    class, 'disableUser']);

    // Kiosks CRUD
    Route::apiResource('kiosks', KioskController::class);
    
    // Password change route
    Route::post('/auth/change-password', [ChangePasswordController::class, 'changePassword']);

    // Kiosk Users CRUD
    Route::get('/kiosk-users', [KioskUserController::class, 'index']);
    Route::post('/kiosk-users', [KioskUserController::class, 'store']);
    Route::get('/kiosk-users/{id}', [KioskUserController::class, 'show']);
    Route::put('/kiosk-users/{id}', [KioskUserController::class, 'update']);
    Route::delete('/kiosk-users/{id}', [KioskUserController::class, 'destroy']);

    // Alias routes for backward compatibility (plural form)
    Route::get('/kiosks-users', [KioskUserController::class, 'index']);
    Route::post('/kiosks-users', [KioskUserController::class, 'store']);
    Route::get('/kiosks-users/{id}', [KioskUserController::class, 'show']);
    Route::put('/kiosks-users/{id}', [KioskUserController::class, 'update']);
    Route::delete('/kiosks-users/{id}', [KioskUserController::class, 'destroy']);
});
