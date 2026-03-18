<?php

namespace App\Http\Controllers\Lgu;

use App\Http\Controllers\Controller;
use App\Models\ActionAuditLog;
use App\Models\Role;
use Illuminate\Http\Request;

class AuditTrailController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->hasAnyRole([Role::SUPER_ADMIN, Role::LGU_ADMIN])) {
            return response()->json(['message' => 'unauthorized', 'data' => null], 403);
        }

        $query = ActionAuditLog::with('actor:id,name,email,role_id,lgu_id');

        if ($user->isSuperAdmin()) {
            if ($request->filled('lgu_id')) {
                $query->where('actor_lgu_id', (int) $request->lgu_id);
            }
        } else {
            $query->where('actor_lgu_id', (int) $user->lgu_id);
        }

        if ($request->filled('actor_user_id')) {
            $query->where('actor_user_id', (int) $request->actor_user_id);
        }

        if ($request->filled('http_method')) {
            $query->where('http_method', strtoupper((string) $request->http_method));
        }

        if ($request->filled('route_path')) {
            $query->where('route_path', 'like', '%' . $request->route_path . '%');
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $perPage = (int) $request->get('per_page', 25);
        $logs = $query->orderByDesc('id')->paginate($perPage);

        return response()->json([
            'message' => 'success',
            'data' => $logs->items(),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ]);
    }
}
