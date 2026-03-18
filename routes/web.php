<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
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

Route::get('/test-simple-mail', function () {
    try {
        // First try with current config
        Mail::raw('This is a test email from JuanCharge production server.', function ($message) {
            $message->to('gercee19@gmail.com')
                    ->subject('Test Email from JuanCharge');
        });
        return 'Simple test email sent successfully to gercee19@gmail.com';
    } catch (\Exception $e) {
        // If SMTP fails, try with log driver to test if it's a connection issue
        try {
            config(['mail.default' => 'log']);
            Mail::raw('Test email logged - SMTP connection failed', function ($message) {
                $message->to('gercee19@gmail.com')
                        ->subject('Test Email Logged');
            });
            return 'SMTP failed but logging worked. Error: ' . $e->getMessage();
        } catch (\Exception $logError) {
            return 'Both SMTP and logging failed. SMTP Error: ' . $e->getMessage() . ' | Log Error: ' . $logError->getMessage();
        }
    }
});

Route::get('/lgu/email/verify/{id}/{hash}', [\App\Http\Controllers\LguUserController::class, 'verifyEmail'])
    ->name('lgu.email.verify');

// Fallback file serving for uploaded assets when /public/storage symlink is unavailable.
Route::get('/storage/{path}', function (string $path) {
    if (str_contains($path, '..')) {
        abort(404);
    }

    if (!Storage::disk('public')->exists($path)) {
        abort(404);
    }

    $content = Storage::disk('public')->get($path);
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $mimeMap = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
        'gif' => 'image/gif',
    ];
    $mimeType = $mimeMap[$ext] ?? 'application/octet-stream';

    return response($content, 200, [
        'Content-Type' => $mimeType,
        'Cache-Control' => 'public, max-age=31536000',
    ]);
})->where('path', '.*');

// require __DIR__.'/auth.php';
