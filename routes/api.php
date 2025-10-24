<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\LguUserController;
use Illuminate\Http\Request;

Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('/auth/user', function(Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// LGU Users CRUD routes
Route::get('/lgu-users', [LguUserController::class, 'index']);
Route::post('/lgu-users', [LguUserController::class, 'store']);
Route::get('/lgu-users/{id}', [LguUserController::class, 'show']);
Route::put('/lgu-users/{id}', [LguUserController::class, 'update']);
Route::delete('/lgu-users/{id}', [LguUserController::class, 'destroy']);
