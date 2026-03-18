<?php

namespace App\Http\Controllers\Lgu;

use App\Http\Controllers\Controller;
use App\Models\CollectionSchedule;
use App\Models\FieldReport;
use App\Models\FieldReportPhoto;
use App\Models\Kiosk;
use App\Models\MaintenanceTicket;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FieldReportController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->hasAnyRole([Role::SUPER_ADMIN, Role::LGU_ADMIN, Role::LGU_STAFF])) {
            return response()->json(['message' => 'unauthorized', 'data' => null], 403);
        }

        $validated = $request->validate([
            'kiosk_id' => 'required|exists:kiosks,id',
            'activity_type' => 'required|in:collection_completed,cleaning,repair',
            'condition_assessment' => 'required|in:good,damaged,needs_attention',
            'notes' => 'required|string|min:5',
            'photos' => 'nullable|array',
            'photos.*' => 'file|mimes:jpeg,jpg,png,webp|max:5120',
        ]);

        if (in_array($validated['activity_type'], ['collection_completed', 'cleaning'], true) && empty($request->file('photos'))) {
            return response()->json([
                'message' => 'validation failed',
                'data' => ['photos' => ['At least one photo is required for collection_completed and cleaning.']],
            ], 422);
        }

        $kiosk = Kiosk::with('collectionSchedule')->findOrFail($validated['kiosk_id']);
        if (!$this->canAccessKiosk($user, $kiosk)) {
            return response()->json(['message' => 'unauthorized', 'data' => null], 403);
        }

        $submittedAt = now();
        $submittedDay = strtolower($submittedAt->format('l'));

        $schedule = $kiosk->collectionSchedule;
        $scheduleDays = $schedule ? (array) $schedule->collection_days : [];

        $alignment = in_array($submittedDay, $scheduleDays, true) ? 'aligned' : 'out_of_schedule';

        $report = DB::transaction(function () use ($validated, $request, $user, $kiosk, $submittedAt, $schedule, $scheduleDays, $alignment) {
            $report = FieldReport::create([
                'kiosk_id' => $kiosk->id,
                'submitted_by_user_id' => $user->id,
                'activity_type' => $validated['activity_type'],
                'condition_assessment' => $validated['condition_assessment'],
                'notes' => $validated['notes'],
                'schedule_name' => $schedule ? $schedule->name : null,
                'schedule_days' => !empty($scheduleDays) ? $scheduleDays : null,
                'schedule_alignment' => $alignment,
                'submitted_at' => $submittedAt,
                'verification_status' => 'pending',
            ]);

            foreach ((array) $request->file('photos', []) as $photo) {
                $path = $photo->store('field-reports/' . $report->id, 'public');
                FieldReportPhoto::create([
                    'field_report_id' => $report->id,
                    'file_path' => $path,
                    'file_url' => asset('storage/' . ltrim($path, '/')),
                    'mime_type' => $photo->getMimeType(),
                    'size_bytes' => $photo->getSize(),
                ]);
            }

            if ($validated['activity_type'] === 'collection_completed') {
                $kiosk->last_serviced_at = $submittedAt;
                $kiosk->save();
            }

            return $report;
        });

        return response()->json([
            'message' => 'success',
            'data' => $report->fresh()->load([
                'photos',
                'kiosk:id,kiosk_code,location,status,lgu_id,last_serviced_at',
                'submitter:id,name,email,lgu_id',
                'verifier:id,name,email',
                'ticket',
            ]),
        ], 201);
    }

    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->hasAnyRole([Role::SUPER_ADMIN, Role::LGU_ADMIN])) {
            return response()->json(['message' => 'unauthorized', 'data' => null], 403);
        }

        $query = FieldReport::with([
            'photos',
            'kiosk:id,kiosk_code,location,status,lgu_id,last_serviced_at',
            'submitter:id,name,email,lgu_id',
            'verifier:id,name,email',
            'ticket',
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
        }

        if ($request->filled('kiosk_id')) {
            $query->where('kiosk_id', (int) $request->kiosk_id);
        }
        if ($request->filled('verification_status')) {
            $query->where('verification_status', $request->verification_status);
        }
        if ($request->filled('activity_type')) {
            $query->where('activity_type', $request->activity_type);
        }
        if ($request->filled('schedule_alignment')) {
            $query->where('schedule_alignment', $request->schedule_alignment);
        }
        if ($request->filled('submitted_by_user_id')) {
            $query->where('submitted_by_user_id', (int) $request->submitted_by_user_id);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('submitted_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('submitted_at', '<=', $request->date_to);
        }
        if ($request->filled('has_ticket')) {
            $hasTicket = filter_var($request->has_ticket, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($hasTicket === true) {
                $query->has('ticket');
            } elseif ($hasTicket === false) {
                $query->doesntHave('ticket');
            }
        }

        $perPage = (int) $request->get('per_page', 15);
        $reports = $query->orderByDesc('submitted_at')->paginate($perPage);

        return response()->json([
            'message' => 'success',
            'data' => $reports->items(),
            'meta' => [
                'current_page' => $reports->currentPage(),
                'last_page' => $reports->lastPage(),
                'per_page' => $reports->perPage(),
                'total' => $reports->total(),
            ],
        ]);
    }

    public function verify(Request $request, int $id)
    {
        $user = $request->user();
        if (!$user || !$user->hasAnyRole([Role::SUPER_ADMIN, Role::LGU_ADMIN])) {
            return response()->json(['message' => 'unauthorized', 'data' => null], 403);
        }

        $report = FieldReport::with('kiosk')->findOrFail($id);
        if (!$this->canAccessKiosk($user, $report->kiosk)) {
            return response()->json(['message' => 'unauthorized', 'data' => null], 403);
        }

        $report->verification_status = 'verified';
        $report->verified_at = now();
        $report->verified_by_user_id = $user->id;
        $report->save();

        return response()->json([
            'message' => 'success',
            'data' => $report->fresh()->load(['photos', 'kiosk', 'submitter', 'verifier', 'ticket']),
        ]);
    }

    public function forceMaintenance(Request $request, int $id)
    {
        $user = $request->user();
        if (!$user || !$user->hasAnyRole([Role::SUPER_ADMIN, Role::LGU_ADMIN])) {
            return response()->json(['message' => 'unauthorized', 'data' => null], 403);
        }

        $report = FieldReport::with('kiosk')->findOrFail($id);
        if (!$this->canAccessKiosk($user, $report->kiosk)) {
            return response()->json(['message' => 'unauthorized', 'data' => null], 403);
        }

        if ($report->condition_assessment !== 'damaged') {
            return response()->json([
                'message' => 'validation failed',
                'data' => ['condition_assessment' => ['Only damaged reports can be forced to maintenance.']],
            ], 422);
        }

        $forced = false;
        if ($report->kiosk && $report->kiosk->status === 'active') {
            $report->kiosk->status = 'under_maintenance';
            $report->kiosk->save();
            $forced = true;
        }

        $report->forced_status_update = $forced;
        $report->forced_status_at = now();
        $report->forced_by_user_id = $user->id;
        $report->save();

        return response()->json([
            'message' => 'success',
            'data' => $report->fresh()->load(['kiosk', 'forcedBy']),
        ]);
    }

    public function createTicket(Request $request, int $id)
    {
        $user = $request->user();
        if (!$user || !$user->hasAnyRole([Role::SUPER_ADMIN, Role::LGU_ADMIN])) {
            return response()->json(['message' => 'unauthorized', 'data' => null], 403);
        }

        $validated = $request->validate([
            'assigned_to_user_id' => 'nullable|exists:users,id',
            'priority' => 'required|in:low,medium,high',
            'issue_summary' => 'required|string|min:5',
        ]);

        $report = FieldReport::with('kiosk')->findOrFail($id);
        if (!$this->canAccessKiosk($user, $report->kiosk)) {
            return response()->json(['message' => 'unauthorized', 'data' => null], 403);
        }

        if (MaintenanceTicket::where('field_report_id', $report->id)->exists()) {
            return response()->json([
                'message' => 'validation failed',
                'data' => ['field_report_id' => ['A maintenance ticket already exists for this report.']],
            ], 422);
        }

        if (!empty($validated['assigned_to_user_id'])) {
            $assignee = User::find($validated['assigned_to_user_id']);
            if (!$assignee || (int) $assignee->lgu_id !== (int) $report->kiosk->lgu_id) {
                return response()->json([
                    'message' => 'validation failed',
                    'data' => ['assigned_to_user_id' => ['Assignee must belong to the same LGU as the kiosk.']],
                ], 422);
            }
        }

        $ticket = DB::transaction(function () use ($validated, $report, $user) {
            $ticket = \App\Models\MaintenanceTicket::create([
                'field_report_id' => $report->id,
                'kiosk_id' => $report->kiosk_id,
                'issue_summary' => $validated['issue_summary'],
                'priority' => $validated['priority'],
                'status' => 'open',
                'assigned_to_user_id' => $validated['assigned_to_user_id'] ?? null,
                'created_by_user_id' => $user->id,
            ]);

            \App\Models\MaintenanceTicketLog::create([
                'maintenance_ticket_id' => $ticket->id,
                'action_type' => 'created',
                'actor_user_id' => $user->id,
                'meta' => [
                    'priority' => $ticket->priority,
                    'assigned_to_user_id' => $ticket->assigned_to_user_id,
                    'issue_summary' => $ticket->issue_summary,
                ],
                'created_at' => now(),
            ]);

            return $ticket;
        });

        return response()->json([
            'message' => 'success',
            'data' => $ticket->fresh()->load(['fieldReport', 'kiosk', 'assignedTo', 'createdBy', 'logs']),
        ], 201);
    }

    private function canAccessKiosk(User $user, Kiosk $kiosk): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->isLguRole() && (int) $user->lgu_id === (int) $kiosk->lgu_id;
    }
}
