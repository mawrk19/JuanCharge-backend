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

    public function handle($request, \Closure $next)
    {
        // Debug logging for every request hitting this middleware
        \Illuminate\Support\Facades\Log::info('VerifyCsrfToken Handle Hit:', [
            'path' => $request->path(),
            'method' => $request->method(),
            'is_except' => $this->inExceptArray($request) ? 'yes' : 'no'
        ]);

        return parent::handle($request, $next);
    }

    protected function inExceptArray($request)
    {
        // 1. Broad Nature-based bypass for APIs (Safe for mobile/stateless clients)
        // We check for 'api/*' and 'auth/*' explicitly to cover all ground
        if (
            $request->is('api/*') || 
            $request->is('auth/*') ||
            $request->expectsJson() ||
            $request->header('X-Requested-With') === 'XMLHttpRequest'
        ) {
            return true;
        }

        // 2. Pattern-based exclusion from $except array
        foreach ($this->except as $except) {
            if ($except !== '/') {
                $except = trim($except, '/');
            }

            if ($request->fullUrlIs($except) || $request->is($except)) {
                return true;
            }
        }

        // 3. Last chance: check parent implementation
        if (parent::inExceptArray($request)) {
            return true;
        }

        // Debug logging for actual mismatches (if they still happen)
        // This is critical for diagnosing why a 419 is still being returned
        \Illuminate\Support\Facades\Log::error('CSRF Mismatch Blocked Request:', [
            'path' => $request->path(),
            'method' => $request->method(),
            'origin' => $request->headers->get('Origin'),
            'referer' => $request->headers->get('Referer'),
            'is_json' => $request->expectsJson() ? 'yes' : 'no'
        ]);

        return false;
    }
}
