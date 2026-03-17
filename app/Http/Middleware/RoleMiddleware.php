<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $roles  Comma separated slugs
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $userRoleSlug = $request->user()->role ? $request->user()->role->slug : null;

        if (!in_array($userRoleSlug, $roles)) {
            return response()->json([
                'message' => 'Your account does not have the required permissions for this action.'
            ], 403);
        }

        return $next($request);
    }
}
