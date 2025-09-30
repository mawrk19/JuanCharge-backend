<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;

Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('/auth/user', function(Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
