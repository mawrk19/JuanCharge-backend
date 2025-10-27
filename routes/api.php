<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\LguUserController;
use App\Http\Controllers\ChangePasswordController;
use Illuminate\Http\Request;

// Authentication routes
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('/auth/user', function(Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Password change route (protected)
Route::post('/auth/change-password', [ChangePasswordController::class, 'changePassword'])
    ->middleware('auth:sanctum');

// LGU Users CRUD routes (protected by authentication)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/lgu-users', [LguUserController::class, 'index']);
    Route::post('/lgu-users', [LguUserController::class, 'store']);
    Route::get('/lgu-users/{id}', [LguUserController::class, 'show']);
    Route::put('/lgu-users/{id}', [LguUserController::class, 'update']);
    Route::delete('/lgu-users/{id}', [LguUserController::class, 'destroy']);
});
