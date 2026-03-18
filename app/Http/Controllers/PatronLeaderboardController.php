<?php

namespace App\Http\Controllers;

use App\Models\LeaderboardSeason;
use App\Services\LeaderboardSeasonService;
use Illuminate\Http\Request;

class PatronLeaderboardController extends Controller
{
    public function current(Request $request, LeaderboardSeasonService $service)
    {
        $season = $service->getOrCreateActiveSeason();
        $ranked = $service->getRankedEntriesForSeason($season, false);

        $willAwardOnClose = $service->willSeasonCloseAwardBonuses($season);
        $bonusMap = [1 => 300, 2 => 200, 3 => 100];

        $data = $ranked->map(function (array $row) use ($willAwardOnClose, $bonusMap) {
            $rank = (int) ($row['rank'] ?? 0);
            $projected = $willAwardOnClose ? ($bonusMap[$rank] ?? 0) : 0;
            $row['projected_bonus_points'] = $projected;
            unset($row['bonus_points_awarded']);
            return $row;
        })->values();

        $nextAwardAt = $service->nextAwardAt();
        $daysToReset = max(0, now()->diffInDays($season->end_at, false));
        $daysToAward = max(0, now()->diffInDays($nextAwardAt, false));

        return response()->json([
            'success' => true,
            'season' => [
                'id' => (int) $season->id,
                'start_at' => optional($season->start_at)->toIso8601String(),
                'end_at' => optional($season->end_at)->toIso8601String(),
                'days_to_reset' => $daysToReset,
            ],
            'reward_cycle' => [
                'next_award_at' => $nextAwardAt->toIso8601String(),
                'days_to_award' => $daysToAward,
                'bonuses' => $service->bonuses(),
            ],
            'data' => $data,
        ]);
    }

    public function seasons(Request $request)
    {
        $perPage = min(max((int) $request->get('per_page', 10), 1), 50);

        $seasons = LeaderboardSeason::with(['entries.user:id,name,email', 'archive'])
            ->where('status', 'closed')
            ->orderByDesc('end_at')
            ->paginate($perPage);

        $data = collect($seasons->items())->map(function (LeaderboardSeason $season) {
            $entries = $season->entries
                ->sortBy('rank')
                ->values();

            $top = $entries
                ->whereNotNull('rank')
                ->sortBy('rank')
                ->take(3)
                ->map(function ($entry) {
                    return [
                        'user_id' => (int) $entry->user_id,
                        'name' => $entry->user->name ?? null,
                        'email' => $entry->user->email ?? null,
                        'rank' => (int) $entry->rank,
                        'total_recycled_weight' => (float) $entry->total_recycled_weight,
                        'bonus_points_awarded' => (int) $entry->bonus_points_awarded,
                    ];
                })
                ->values();

            return [
                'id' => (int) $season->id,
                'start_at' => optional($season->start_at)->toIso8601String(),
                'end_at' => optional($season->end_at)->toIso8601String(),
                'status' => $season->status,
                'total_recycled_weight' => (float) $entries->sum('total_recycled_weight'),
                'top_performers' => $top,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'current_page' => $seasons->currentPage(),
                'last_page' => $seasons->lastPage(),
                'per_page' => $seasons->perPage(),
                'total' => $seasons->total(),
            ],
        ]);
    }
}
