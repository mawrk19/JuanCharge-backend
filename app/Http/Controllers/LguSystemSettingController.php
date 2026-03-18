<?php

namespace App\Http\Controllers;

use App\Models\LguSystemSetting;
use Illuminate\Http\Request;

class LguSystemSettingController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();

        if ($user->isSuperAdmin()) {
            $validated = $request->validate([
                'lgu_id' => 'required|exists:lgus,id',
            ]);
            $lguId = (int) $validated['lgu_id'];
        } else {
            if (!$user->isLguRole()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            $lguId = (int) $user->lgu_id;
        }

        $setting = LguSystemSetting::firstOrCreate(
            ['lgu_id' => $lguId],
            [
                'minutes_per_bottle' => 0,
                'minutes_per_kg' => 0,
                'points_per_kg' => 0,
            ]
        );

        return response()->json(['success' => true, 'data' => $setting]);
    }

    public function upsert(Request $request)
    {
        $user = $request->user();

        if (!$user->isSuperAdmin() && !$user->isLguAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'lgu_id' => 'nullable|exists:lgus,id',
            'minutes_per_bottle' => 'required|numeric|min:0|max:1440',
            'minutes_per_kg' => 'required|numeric|min:0|max:1440',
            'points_per_kg' => 'nullable|integer|min:0|max:100000',
        ]);

        $targetLguId = $user->isSuperAdmin()
            ? (int) ($validated['lgu_id'] ?? 0)
            : (int) $user->lgu_id;

        if ($targetLguId <= 0) {
            return response()->json(['success' => false, 'message' => 'LGU is required.'], 422);
        }

        $setting = LguSystemSetting::updateOrCreate(
            ['lgu_id' => $targetLguId],
            [
                'minutes_per_bottle' => $validated['minutes_per_bottle'],
                'minutes_per_kg' => $validated['minutes_per_kg'],
                'points_per_kg' => $validated['points_per_kg'] ?? 0,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'System exchange rates updated successfully.',
            'data' => $setting,
        ]);
    }
}
