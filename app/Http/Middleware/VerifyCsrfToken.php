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
        // 1. Broad Nature-based bypass for APIs (Safe for mobile/stateless clients)
        if (
            $request->expectsJson() ||
            $request->header('X-Requested-With') === 'XMLHttpRequest' ||
            $request->is('api/*') ||
            $request->is('auth/*')
        ) {
            return true;
        }

        // 2. Pattern-based fallback
        foreach ($this->except as $except) {
            if ($except !== '/') {
                $except = trim($except, '/');
            }

            if ($request->fullUrlIs($except) || $request->is($except)) {
                return true;
            }
        }

        // 3. Fallback to pattern check (should be covered by nature-based but for safety)
        if (parent::inExceptArray($request)) {
            return true;
        }

        // Debug logging for actual mismatches (if they still happen)
        \Illuminate\Support\Facades\Log::warning('CSRF Mismatch Debug:', [
            'path' => $request->path(),
            'method' => $request->method(),
            'origin' => $request->headers->get('Origin'),
            'accept' => $request->headers->get('Accept'),
            'is_json' => $request->expectsJson() ? 'yes' : 'no'
        ]);

        return false;
    }
}
