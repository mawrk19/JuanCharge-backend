<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\LguUserController;
use App\Http\Controllers\KioskController;
use App\Http\Controllers\KioskUserController;
use App\Http\Controllers\ChargingController;
use App\Http\Controllers\MobileAuthController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/', function () {
    return response()->json(['message' => 'API is working']);
});
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [KioskUserController::class, 'register']);

// Mobile-specific auth routes (for patron/kiosk users)
Route::middleware(['mobile-api'])->group(function () {
    // OTP Routes (Matches user's request: /api/auth/otp/start)
    Route::post('/auth/otp/start', [MobileAuthController::class, 'startOtp'])->middleware('throttle:3,1');
    Route::post('/auth/otp/verify', [MobileAuthController::class, 'verifyOtp'])->middleware('throttle:6,1');
    
    // Legacy/Direct Login Routes
    Route::post('/auth/mobile/login', [MobileAuthController::class, 'mobileLogin']);
    Route::post('/auth/mobile/auto-login', [MobileAuthController::class, 'autoLogin']);
    Route::post('/auth/mobile/refresh-token', [MobileAuthController::class, 'refreshDeviceToken']);
    Route::get('/auth/mobile/debug-check', [MobileAuthController::class, 'debugCheck']); 

    // Protected mobile routes (Bearer token required)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/mobile/logout', [MobileAuthController::class, 'mobileLogout']);
    });
});

// Password reset routes (public)
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);

// Protected routes (require Sanctum authentication)
Route::middleware('auth:sanctum')->group(function () {
// Auth routes
Route::post('/auth/logout', [AuthController::class, 'logout']);
Route::post('/auth/refresh', [AuthController::class, 'refresh']);
Route::get('/auth/me', [AuthController::class, 'me']);
Route::get('/auth/validate', [AuthController::class, 'validateToken']);
Route::put('/auth/profile', [AuthController::class, 'updateProfile']);
Route::post('/auth/change-password', [AuthController::class, 'changePassword']);

// LGU Users CRUD
Route::get('/lgu-users', [LguUserController::class, 'index']);
Route::post('/lgu-users', [LguUserController::class, 'store']);
Route::get('/lgu-users/{id}', [LguUserController::class, 'show']);
Route::put('/lgu-users/{id}', [LguUserController::class, 'update']);
Route::delete('/lgu-users/{id}', [LguUserController::class, 'destroy']);
Route::patch('/lgu-users/{id}/disable', [LguUserController::    class, 'disableUser']);

// Kiosks CRUD
Route::apiResource('kiosks', KioskController::class);

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

// Charging Session Routes
Route::post('/charging/redeem', [ChargingController::class, 'redeem']);
Route::get('/charging/active', [ChargingController::class, 'getActive']);
Route::post('/charging/cancel', [ChargingController::class, 'cancel']);
Route::get('/charging/history', [ChargingController::class, 'history']);

// Points Routes
Route::get('/patron/points/balance', [ChargingController::class, 'getBalance']);
Route::get('/patron/points/transactions', [ChargingController::class, 'transactions']);

// Recycling Routes
Route::post('/patron/recycling/deposit', [ChargingController::class, 'depositRecyclables']);

// Dashboard Stats & Social
Route::get('/patron/dashboard/stats', [ChargingController::class, 'getDashboardStats']);
Route::get('/patron/leaderboard', [ChargingController::class, 'getLeaderboard']);
Route::get('/patron/achievements', [ChargingController::class, 'getAchievements']);
});
