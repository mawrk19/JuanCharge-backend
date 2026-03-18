<?php

namespace App\Http\Controllers;

use App\Models\CollectionNotification;
use Illuminate\Http\Request;

class CollectionNotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user->isSuperAdmin() && !$user->isLguRole()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $query = CollectionNotification::with([
            'kiosk:id,kiosk_code,location',
            'schedule:id,name,collection_days,notify_time',
        ]);

        if ($user->isSuperAdmin()) {
            if ($request->filled('lgu_id')) {
                $query->where('lgu_id', $request->get('lgu_id'));
            }
            if ($request->filled('user_id')) {
                $query->where('user_id', $request->get('user_id'));
            }
        } else {
            $query->where('user_id', $user->id);
        }

        $notifications = $query->orderByDesc('id')->paginate((int) $request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $notifications->items(),
            'pagination' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'total' => $notifications->total(),
            ],
        ]);
    }

    public function markAsRead(Request $request, int $id)
    {
        $user = $request->user();
        $notification = CollectionNotification::findOrFail($id);

        if (!$user->isSuperAdmin() && (int) $notification->user_id !== (int) $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if (!$notification->read_at) {
            $notification->read_at = now();
            $notification->save();
        }

        return response()->json(['success' => true, 'message' => 'Notification marked as read.', 'data' => $notification]);
    }
}
