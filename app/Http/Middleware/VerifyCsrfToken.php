<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'api/*',          // Broad exclusion for test
        'api/auth/login',
        'api/auth/logout',
        'api/auth/register',
        'api/auth/otp/*', // OTP endpoints bypass CSRF
        'auth/otp/*',     // Non-prefixed fallback
        'api/mobile/*',   // All mobile endpoints bypass CSRF
        'mobile/*',       // Non-prefixed fallback
    ];

    /**
     * Determine if the request has a URI that should pass through CSRF verification.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function inExceptArray($request)
    {
        foreach ($this->except as $except) {
            if ($except !== '/') {
                $except = trim($except, '/');
            }

            if ($request->fullUrlIs($except) || $request->is($except)) {
                return true;
            }
        }

        // Debug logging for mismatch
        if ($request->is('api/*') || $request->is('auth/*')) {
            \Illuminate\Support\Facades\Log::warning('CSRF Mismatch Debug:', [
                'path' => $request->path(),
                'full_url' => $request->fullUrl(),
                'origin' => $request->headers->get('Origin'),
                'is_stateful' => $request->attributes->get('sanctum') ? 'yes' : 'no'
            ]);
        }

        return false;
    }
}
