<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TestMailController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});

Route::get('/test-lgu-mail', [TestMailController::class, 'sendTestLguWelcomeEmail']);

Route::get('/lgu/email/verify/{id}/{hash}', [\App\Http\Controllers\LguUserController::class, 'verifyEmail'])
    ->name('lgu.email.verify');

// require __DIR__.'/auth.php';
