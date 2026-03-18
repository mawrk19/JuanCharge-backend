<?php

namespace App\Http\Controllers;

use App\Models\CollectionNotification;
use App\Models\CollectionSchedule;
use App\Models\Kiosk;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;

class CollectionScheduleController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user->isSuperAdmin() && !$user->isLguRole()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $query = CollectionSchedule::with(['lgu:id,name', 'kiosks:id,kiosk_code,location,lgu_id,collection_schedule_id']);

        if ($user->isSuperAdmin()) {
            if ($request->filled('lgu_id')) {
                $query->where('lgu_id', $request->get('lgu_id'));
            }
        } else {
            $query->where('lgu_id', $user->lgu_id);
        }

        $schedules = $query->orderByDesc('id')->get();

        return response()->json(['success' => true, 'data' => $schedules]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        if (!$user->isSuperAdmin() && !$user->isLguAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'lgu_id' => 'nullable|exists:lgus,id',
            'collection_days' => 'required|array|min:1',
            'collection_days.*' => 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'notify_time' => 'nullable|date_format:H:i',
            'is_active' => 'nullable|boolean',
        ]);

        $lguId = $user->isSuperAdmin()
            ? (int) ($validated['lgu_id'] ?? 0)
            : (int) $user->lgu_id;

        if ($lguId <= 0) {
            return response()->json(['success' => false, 'message' => 'LGU is required.'], 422);
        }

        $schedule = CollectionSchedule::create([
            'lgu_id' => $lguId,
            'name' => $validated['name'],
            'collection_days' => array_values(array_unique($validated['collection_days'])),
            'notify_time' => $validated['notify_time'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json(['success' => true, 'message' => 'Collection schedule created.', 'data' => $schedule], 201);
    }

    public function show(Request $request, int $id)
    {
        $user = $request->user();
        $schedule = CollectionSchedule::with(['lgu:id,name', 'kiosks:id,kiosk_code,location,lgu_id,collection_schedule_id'])->findOrFail($id);

        if (!$this->canAccessSchedule($user, $schedule)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        return response()->json(['success' => true, 'data' => $schedule]);
    }

    public function update(Request $request, int $id)
    {
        $user = $request->user();
        $schedule = CollectionSchedule::findOrFail($id);

        if (!$this->canManageSchedule($user, $schedule)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'collection_days' => 'sometimes|array|min:1',
            'collection_days.*' => 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'notify_time' => 'nullable|date_format:H:i',
            'is_active' => 'sometimes|boolean',
        ]);

        if (isset($validated['collection_days'])) {
            $validated['collection_days'] = array_values(array_unique($validated['collection_days']));
        }

        $schedule->update($validated);

        return response()->json(['success' => true, 'message' => 'Collection schedule updated.', 'data' => $schedule->fresh()]);
    }

    public function destroy(Request $request, int $id)
    {
        $user = $request->user();
        $schedule = CollectionSchedule::findOrFail($id);

        if (!$this->canManageSchedule($user, $schedule)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // Unbind kiosks before deleting schedule.
        Kiosk::where('collection_schedule_id', $schedule->id)->update(['collection_schedule_id' => null]);
        $schedule->delete();

        return response()->json(['success' => true, 'message' => 'Collection schedule deleted.']);
    }

    public function notify(Request $request, int $id)
    {
        $user = $request->user();
        $schedule = CollectionSchedule::with(['kiosks:id,kiosk_code,location,lgu_id,collection_schedule_id'])->findOrFail($id);

        if (!$this->canManageSchedule($user, $schedule)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $recipientUsers = User::where('lgu_id', $schedule->lgu_id)
            ->whereIn('role_id', [Role::LGU_ADMIN, Role::LGU_STAFF])
            ->get(['id']);

        $count = 0;
        foreach ($schedule->kiosks as $kiosk) {
            foreach ($recipientUsers as $recipient) {
                CollectionNotification::create([
                    'user_id' => $recipient->id,
                    'lgu_id' => $schedule->lgu_id,
                    'collection_schedule_id' => $schedule->id,
                    'kiosk_id' => $kiosk->id,
                    'title' => 'Collection Reminder',
                    'message' => 'Collection scheduled for kiosk ' . $kiosk->kiosk_code . ' (' . $kiosk->location . ').',
                    'scheduled_for' => now(),
                ]);
                $count++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Collection notifications created.',
            'data' => ['notifications_created' => $count],
        ]);
    }

    private function canAccessSchedule(User $user, CollectionSchedule $schedule): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->isLguRole() && (int) $user->lgu_id === (int) $schedule->lgu_id;
    }

    private function canManageSchedule(User $user, CollectionSchedule $schedule): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->isLguAdmin() && (int) $user->lgu_id === (int) $schedule->lgu_id;
    }
}
