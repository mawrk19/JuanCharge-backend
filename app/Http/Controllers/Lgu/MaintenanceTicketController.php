<?php

namespace App\Http\Controllers\Lgu;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceTicket;
use App\Models\MaintenanceTicketLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;

class MaintenanceTicketController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->hasAnyRole([Role::SUPER_ADMIN, Role::LGU_ADMIN, Role::LGU_TECHNICIAN])) {
            return response()->json(['message' => 'unauthorized', 'data' => null], 403);
        }

        $query = MaintenanceTicket::with([
            'fieldReport:id,kiosk_id,activity_type,condition_assessment,submitted_at,verification_status',
            'kiosk:id,kiosk_code,location,lgu_id,status,last_serviced_at',
            'assignedTo:id,name,email,lgu_id',
            'createdBy:id,name,email',
            'closedBy:id,name,email',
            'logs.actor:id,name,email',
        ]);

        if ($user->isSuperAdmin()) {
            if ($request->filled('lgu_id')) {
                $query->whereHas('kiosk', function ($q) use ($request) {
                    $q->where('lgu_id', (int) $request->lgu_id);
                });
            }
        } else {
            $query->whereHas('kiosk', function ($q) use ($user) {
                $q->where('lgu_id', (int) $user->lgu_id);
            });

            // Technicians should only see tickets assigned to themselves.
            if ($user->isLguTechnician()) {
                $query->where('assigned_to_user_id', (int) $user->id);
            }
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }
        if ($request->filled('assigned_to_user_id')) {
            $query->where('assigned_to_user_id', (int) $request->assigned_to_user_id);
        }
        if ($request->filled('kiosk_id')) {
            $query->where('kiosk_id', (int) $request->kiosk_id);
        }

        $perPage = (int) $request->get('per_page', 15);
        $tickets = $query->orderByDesc('id')->paginate($perPage);

        return response()->json([
            'message' => 'success',
            'data' => $tickets->items(),
            'meta' => [
                'current_page' => $tickets->currentPage(),
                'last_page' => $tickets->lastPage(),
                'per_page' => $tickets->perPage(),
                'total' => $tickets->total(),
            ],
        ]);
    }

    public function update(Request $request, int $id)
    {
        $user = $request->user();
        if (!$user || !$user->hasAnyRole([Role::SUPER_ADMIN, Role::LGU_ADMIN])) {
            return response()->json(['message' => 'unauthorized', 'data' => null], 403);
        }

        $ticket = MaintenanceTicket::with('kiosk')->findOrFail($id);
        if (!$this->canAccessTicket($user, $ticket)) {
            return response()->json(['message' => 'unauthorized', 'data' => null], 403);
        }

        $validated = $request->validate([
            'assigned_to_user_id' => 'nullable|exists:users,id',
            'priority' => 'sometimes|in:low,medium,high',
            'status' => 'sometimes|in:open,in_progress,closed',
        ]);

        if (array_key_exists('assigned_to_user_id', $validated) && !empty($validated['assigned_to_user_id'])) {
            $assignee = User::find($validated['assigned_to_user_id']);
            if (
                !$assignee
                || (int) $assignee->lgu_id !== (int) $ticket->kiosk->lgu_id
                || !$assignee->hasAnyRole([Role::LGU_STAFF, Role::LGU_TECHNICIAN])
            ) {
                return response()->json([
                    'message' => 'validation failed',
                    'data' => ['assigned_to_user_id' => ['Assignee must be an LGU staff/technician in the same LGU as the kiosk.']],
                ], 422);
            }
        }

        $changes = [];

        if (array_key_exists('assigned_to_user_id', $validated) && (int) $ticket->assigned_to_user_id !== (int) $validated['assigned_to_user_id']) {
            $changes[] = [
                'action_type' => 'assigned',
                'meta' => ['from' => $ticket->assigned_to_user_id, 'to' => $validated['assigned_to_user_id']],
            ];
            $ticket->assigned_to_user_id = $validated['assigned_to_user_id'];
        }

        if (isset($validated['priority']) && $ticket->priority !== $validated['priority']) {
            $changes[] = [
                'action_type' => 'priority_changed',
                'meta' => ['from' => $ticket->priority, 'to' => $validated['priority']],
            ];
            $ticket->priority = $validated['priority'];
        }

        if (isset($validated['status']) && $ticket->status !== $validated['status']) {
            $changes[] = [
                'action_type' => 'status_changed',
                'meta' => ['from' => $ticket->status, 'to' => $validated['status']],
            ];
            $ticket->status = $validated['status'];
        }

        $ticket->save();

        foreach ($changes as $change) {
            MaintenanceTicketLog::create([
                'maintenance_ticket_id' => $ticket->id,
                'action_type' => $change['action_type'],
                'actor_user_id' => $user->id,
                'meta' => $change['meta'],
                'created_at' => now(),
            ]);
        }

        return response()->json([
            'message' => 'success',
            'data' => $ticket->fresh()->load(['fieldReport', 'kiosk', 'assignedTo', 'createdBy', 'closedBy', 'logs.actor']),
        ]);
    }

    public function close(Request $request, int $id)
    {
        $user = $request->user();
        if (!$user || !$user->hasAnyRole([Role::SUPER_ADMIN, Role::LGU_ADMIN, Role::LGU_TECHNICIAN])) {
            return response()->json(['message' => 'unauthorized', 'data' => null], 403);
        }

        $ticket = MaintenanceTicket::with('kiosk')->findOrFail($id);
        if (!$this->canAccessTicket($user, $ticket)) {
            return response()->json(['message' => 'unauthorized', 'data' => null], 403);
        }

        $validated = $request->validate([
            'technician_follow_up' => 'required|string|min:3',
            'resolution_log' => 'required|string|min:3',
        ]);

        $ticket->status = 'closed';
        $ticket->closed_at = now();
        $ticket->closed_by_user_id = $user->id;
        $ticket->technician_follow_up = $validated['technician_follow_up'];
        $ticket->resolution_log = $validated['resolution_log'];
        $ticket->save();

        MaintenanceTicketLog::create([
            'maintenance_ticket_id' => $ticket->id,
            'action_type' => 'closed',
            'actor_user_id' => $user->id,
            'meta' => [
                'technician_follow_up' => $ticket->technician_follow_up,
                'resolution_log' => $ticket->resolution_log,
            ],
            'created_at' => now(),
        ]);

        return response()->json([
            'message' => 'success',
            'data' => $ticket->fresh()->load(['fieldReport', 'kiosk', 'assignedTo', 'createdBy', 'closedBy', 'logs.actor']),
        ]);
    }

    private function canAccessTicket(User $user, MaintenanceTicket $ticket): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isLguAdmin()) {
            return (int) $user->lgu_id === (int) $ticket->kiosk->lgu_id;
        }

        if ($user->isLguTechnician()) {
            return (int) $user->lgu_id === (int) $ticket->kiosk->lgu_id
                && (int) $ticket->assigned_to_user_id === (int) $user->id;
        }

        return false;
    }
}
