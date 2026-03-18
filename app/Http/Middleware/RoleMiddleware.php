<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;

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
        /** @var User|null $user */
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $normalizedRoles = collect($roles)
            ->flatMap(function ($role) {
                return explode('|', (string) $role);
            })
            ->map(function ($role) {
                $value = trim((string) $role);
                if ($value === '') {
                    return null;
                }

                if (is_numeric($value)) {
                    return (int) $value;
                }

                return $value;
            })
            ->filter()
            ->values()
            ->all();

        if (empty($normalizedRoles) || !$user->hasAnyRole($normalizedRoles)) {
            return response()->json([
                'message' => 'Your account does not have the required permissions for this action.'
            ], 403);
        }

        return $next($request);
    }
}
