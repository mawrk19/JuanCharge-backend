<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\LeaderboardSeasonService;
use Illuminate\Http\Request;

class LeaderboardAdminController extends Controller
{
    public function refresh(Request $request, LeaderboardSeasonService $service)
    {
        $validated = $request->validate([
            'force_close' => 'nullable|boolean',
            'season_id' => 'nullable|integer|exists:leaderboard_seasons,id',
        ]);

        $forceClose = (bool) ($validated['force_close'] ?? false);
        $seasonId = $validated['season_id'] ?? null;

        if ($forceClose && !$seasonId) {
            return response()->json([
                'success' => false,
                'message' => 'season_id is required when force_close is true for idempotent retries.',
            ], 422);
        }

        $result = $service->refresh(
            forceClose: $forceClose,
            actor: $request->user(),
            targetSeasonId: $seasonId ? (int) $seasonId : null,
        );

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }
}
