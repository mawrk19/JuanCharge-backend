<?php

namespace App\Http\Middleware;

use App\Models\ActionAuditLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Response;

class ApiAuditTrailMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Skip high-frequency machine heartbeat endpoint to avoid audit log noise.
        if ($request->is('api/kiosk/heartbeat')) {
            return $response;
        }

        // Keep logs focused on state-changing API calls.
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return $response;
        }

        $route = $request->route();
        $user = $request->user();

        $payload = $this->sanitizePayload($request->all());
        $fileMeta = $this->extractFileMeta($request);
        if (!empty($fileMeta)) {
            $payload['_files'] = $fileMeta;
        }

        ActionAuditLog::create([
            'actor_user_id' => $user ? $user->id : null,
            'actor_role_id' => $user ? $user->role_id : null,
            'actor_lgu_id' => $user ? $user->lgu_id : null,
            'http_method' => $request->method(),
            'route_path' => '/' . ltrim($request->path(), '/'),
            'route_name' => $route ? $route->getName() : null,
            'controller_action' => $route ? $route->getActionName() : null,
            'status_code' => $response->getStatusCode(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'query_params' => $request->query(),
            'payload' => $payload,
            'response_meta' => [
                'content_type' => $response->headers->get('Content-Type'),
            ],
            'created_at' => now(),
        ]);

        return $response;
    }

    private function sanitizePayload(array $payload): array
    {
        $sensitiveKeys = [
            'password',
            'password_confirmation',
            'current_password',
            'new_password',
            'token',
            'device_token',
            'authorization',
        ];

        foreach ($sensitiveKeys as $key) {
            if (Arr::has($payload, $key)) {
                Arr::set($payload, $key, '[REDACTED]');
            }
        }

        // Avoid oversized audit rows from raw binary arrays.
        if (isset($payload['photos'])) {
            $payload['photos'] = is_array($payload['photos'])
                ? ['count' => count($payload['photos'])]
                : '[UPLOADED_FILE]';
        }

        return $payload;
    }

    private function extractFileMeta(Request $request): array
    {
        $files = [];

        foreach ($request->allFiles() as $key => $fileValue) {
            if (is_array($fileValue)) {
                $files[$key] = array_map(function ($f) {
                    return [
                        'original_name' => $f->getClientOriginalName(),
                        'size_bytes' => $f->getSize(),
                        'mime_type' => $f->getMimeType(),
                    ];
                }, $fileValue);
            } else {
                $files[$key] = [
                    'original_name' => $fileValue->getClientOriginalName(),
                    'size_bytes' => $fileValue->getSize(),
                    'mime_type' => $fileValue->getMimeType(),
                ];
            }
        }

        return $files;
    }
}
