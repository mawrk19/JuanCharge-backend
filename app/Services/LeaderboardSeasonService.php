<?php

namespace App\Services;

use App\Models\ActionAuditLog;
use App\Models\LeaderboardArchive;
use App\Models\LeaderboardEntry;
use App\Models\LeaderboardSeason;
use App\Models\PointsTransaction;
use App\Models\RecyclingLog;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class LeaderboardSeasonService
{
    public const SEASON_DAYS = 14;
    public const REWARD_CYCLE_DAYS = 21;

    private const PET_BOTTLE_WEIGHT_G = 10.5;
    private const ALUMINUM_CAN_WEIGHT_G = 14.0;
    private const TIN_CAN_WEIGHT_G = 42.5;

    private const BONUS_BY_RANK = [
        1 => 300,
        2 => 200,
        3 => 100,
    ];

    public function getOrCreateActiveSeason(?Carbon $now = null): LeaderboardSeason
    {
        $now = $now ?: now();

        $season = LeaderboardSeason::where('status', 'active')->orderByDesc('id')->first();
        if ($season) {
            return $season;
        }

        $start = $now->copy();
        $end = $start->copy()->addDays(self::SEASON_DAYS);

        return LeaderboardSeason::create([
            'start_at' => $start,
            'end_at' => $end,
            'status' => 'active',
        ]);
    }

    public function refresh(bool $forceClose = false, ?User $actor = null, ?int $targetSeasonId = null): array
    {
        $now = now();
        $lock = null;

        try {
            $lock = Cache::lock('leaderboard:season-refresh', 20);
            if (!$lock->get()) {
                return [
                    'status' => 'locked',
                    'message' => 'Leaderboard refresh is already running.',
                ];
            }
        } catch (\Throwable $e) {
            Log::warning('Cache lock unavailable for leaderboard refresh, continuing with DB transaction lock.', [
                'error' => $e->getMessage(),
            ]);
        }

        try {
            return DB::transaction(function () use ($forceClose, $actor, $targetSeasonId, $now) {
                $activeSeason = LeaderboardSeason::where('status', 'active')
                    ->orderByDesc('id')
                    ->lockForUpdate()
                    ->first();

                if (!$activeSeason) {
                    $activeSeason = $this->getOrCreateActiveSeason($now);
                }

                $this->materializeSeasonEntries($activeSeason);

                if ($targetSeasonId !== null && (int) $activeSeason->id !== (int) $targetSeasonId) {
                    return [
                        'status' => 'already_processed',
                        'message' => 'Target season is no longer active.',
                        'active_season_id' => (int) $activeSeason->id,
                    ];
                }

                if (!$forceClose && $now->lt($activeSeason->end_at)) {
                    return [
                        'status' => 'noop',
                        'message' => 'Active season has not ended yet.',
                        'active_season_id' => (int) $activeSeason->id,
                    ];
                }

                $closeResult = $this->closeSeasonAndCreateNext($activeSeason, $actor, $forceClose);

                return [
                    'status' => 'closed_and_rolled',
                    'message' => 'Season closed and next season created.',
                    'closed_season_id' => (int) $activeSeason->id,
                    'new_active_season_id' => (int) $closeResult['new_season_id'],
                    'bonus_awarded' => (bool) $closeResult['bonus_awarded'],
                    'bonuses' => $closeResult['bonuses'],
                ];
            }, 3);
        } finally {
            if ($lock) {
                try {
                    $lock->release();
                } catch (\Throwable $e) {
                    // No-op
                }
            }
        }
    }

    public function materializeSeasonEntries(LeaderboardSeason $season): Collection
    {
        $userWeights = [];

        $rows = RecyclingLog::query()
            ->selectRaw('user_id, item_type, COALESCE(SUM(weight_kg), 0) as total_weight_kg, COALESCE(SUM(`count`), 0) as total_count')
            ->whereNotNull('user_id')
            ->whereRaw('COALESCE(hardware_timestamp, created_at) >= ? AND COALESCE(hardware_timestamp, created_at) < ?', [
                $season->start_at,
                $season->end_at,
            ])
            ->groupBy('user_id', 'item_type')
            ->get();

        foreach ($rows as $row) {
            $userId = (int) $row->user_id;
            if (!isset($userWeights[$userId])) {
                $userWeights[$userId] = 0.0;
            }

            $itemType = $this->normalizeItemTypeForSeason((string) ($row->item_type ?? ''));
            $totalCount = (int) ($row->total_count ?? 0);
            $measuredWeightKg = (float) ($row->total_weight_kg ?? 0);

            if ($measuredWeightKg > 0) {
                $userWeights[$userId] += $measuredWeightKg;
            } else {
                $userWeights[$userId] += $this->estimateWeightKgForItemType($itemType, $totalCount);
            }
        }

        $existingUserIds = LeaderboardEntry::where('season_id', $season->id)
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        foreach ($userWeights as $userId => $weightKg) {
            LeaderboardEntry::updateOrCreate(
                [
                    'season_id' => $season->id,
                    'user_id' => $userId,
                ],
                [
                    'total_recycled_weight' => round($weightKg, 3),
                    'rank' => null,
                ]
            );
        }

        $staleUserIds = array_values(array_diff($existingUserIds, array_keys($userWeights)));
        if (!empty($staleUserIds) && $season->status === 'active') {
            LeaderboardEntry::where('season_id', $season->id)
                ->whereIn('user_id', $staleUserIds)
                ->delete();
        }

        return LeaderboardEntry::with(['user:id,name,email,role_id'])
            ->where('season_id', $season->id)
            ->whereHas('user', function ($q) {
                $q->where('role_id', Role::KIOSK_USER);
            })
            ->orderByDesc('total_recycled_weight')
            ->orderBy('user_id')
            ->get();
    }

    public function getRankedEntriesForSeason(LeaderboardSeason $season, bool $persistRanks = false): Collection
    {
        $entries = $this->materializeSeasonEntries($season)
            ->values();

        $ranked = $entries->map(function (LeaderboardEntry $entry, int $index) use ($persistRanks) {
            $rank = $index + 1;
            if ($persistRanks && (int) ($entry->rank ?? 0) !== $rank) {
                $entry->rank = $rank;
                $entry->save();
            }

            return [
                'user_id' => (int) $entry->user_id,
                'name' => $entry->user->name ?? null,
                'email' => $entry->user->email ?? null,
                'total_recycled_weight' => (float) $entry->total_recycled_weight,
                'rank' => $persistRanks ? (int) $entry->rank : $rank,
                'bonus_points_awarded' => (int) $entry->bonus_points_awarded,
            ];
        });

        return $ranked;
    }

    public function willSeasonCloseAwardBonuses(LeaderboardSeason $season): bool
    {
        $anchor = $this->rewardCycleAnchor();
        $periodSeconds = self::REWARD_CYCLE_DAYS * 86400;
        $elapsed = $anchor->diffInSeconds($season->end_at, false);

        return $elapsed >= 0 && ($elapsed % $periodSeconds) === 0;
    }

    public function nextAwardAt(?Carbon $now = null): Carbon
    {
        $now = $now ?: now();
        $anchor = $this->rewardCycleAnchor();
        $periodSeconds = self::REWARD_CYCLE_DAYS * 86400;

        if ($now->lte($anchor)) {
            return $anchor->copy()->addDays(self::REWARD_CYCLE_DAYS);
        }

        $elapsed = $anchor->diffInSeconds($now, false);
        $cyclesPassed = intdiv(max(0, $elapsed), $periodSeconds);
        $nextTs = $anchor->timestamp + (($cyclesPassed + 1) * $periodSeconds);

        return Carbon::createFromTimestamp($nextTs, $now->getTimezone());
    }

    public function bonuses(): array
    {
        return array_values(self::BONUS_BY_RANK);
    }

    private function closeSeasonAndCreateNext(LeaderboardSeason $season, ?User $actor = null, bool $manual = false): array
    {
        $ranked = $this->getRankedEntriesForSeason($season, true);

        $bonusAwarded = false;
        $bonusResults = [];

        if ($this->willSeasonCloseAwardBonuses($season)) {
            foreach (self::BONUS_BY_RANK as $rank => $bonus) {
                $entry = LeaderboardEntry::where('season_id', $season->id)
                    ->where('rank', $rank)
                    ->lockForUpdate()
                    ->first();

                if (!$entry) {
                    continue;
                }

                if ((int) $entry->bonus_points_awarded > 0) {
                    $bonusResults[] = [
                        'rank' => $rank,
                        'user_id' => (int) $entry->user_id,
                        'points' => (int) $entry->bonus_points_awarded,
                        'status' => 'already_awarded',
                    ];
                    continue;
                }

                $user = User::find($entry->user_id);
                if (!$user) {
                    continue;
                }

                $entry->bonus_points_awarded = $bonus;
                $entry->save();

                $user->points_balance = (int) $user->points_balance + $bonus;
                $user->points_total = (int) $user->points_total + $bonus;
                $user->save();

                PointsTransaction::create([
                    'user_id' => $user->id,
                    'transaction_type' => 'bonus',
                    'type' => 'leaderboard',
                    'title' => 'Leaderboard Season Bonus',
                    'points' => $bonus,
                    'status' => 'completed',
                    'balance_after' => $user->points_balance,
                    'reference_type' => 'leaderboard_season',
                    'reference_id' => $season->id,
                    'description' => 'Leaderboard top-' . $rank . ' bonus award',
                ]);

                $bonusAwarded = true;
                $bonusResults[] = [
                    'rank' => $rank,
                    'user_id' => (int) $entry->user_id,
                    'points' => $bonus,
                    'status' => 'awarded',
                ];
            }
        }

        $season->status = 'closed';
        $season->save();

        LeaderboardArchive::updateOrCreate(
            ['season_id' => $season->id],
            [
                'snapshot_json' => [
                    'season' => [
                        'id' => (int) $season->id,
                        'start_at' => optional($season->start_at)->toIso8601String(),
                        'end_at' => optional($season->end_at)->toIso8601String(),
                        'status' => $season->status,
                    ],
                    'data' => $ranked,
                    'total_recycled_weight' => (float) $ranked->sum('total_recycled_weight'),
                    'bonus_awarded' => $bonusAwarded,
                    'bonus_details' => $bonusResults,
                ],
            ]
        );

        $newSeason = LeaderboardSeason::create([
            'start_at' => $season->end_at,
            'end_at' => $season->end_at->copy()->addDays(self::SEASON_DAYS),
            'status' => 'active',
        ]);

        $this->logAuditEvent('leaderboard/season_close', $actor, [
            'manual' => $manual,
            'season_id' => (int) $season->id,
            'season_start_at' => optional($season->start_at)->toIso8601String(),
            'season_end_at' => optional($season->end_at)->toIso8601String(),
            'bonus_awarded' => $bonusAwarded,
            'bonus_details' => $bonusResults,
            'next_season_id' => (int) $newSeason->id,
            'next_season_start_at' => optional($newSeason->start_at)->toIso8601String(),
            'next_season_end_at' => optional($newSeason->end_at)->toIso8601String(),
        ]);

        if ($bonusAwarded) {
            $this->logAuditEvent('leaderboard/bonus_award', $actor, [
                'season_id' => (int) $season->id,
                'bonus_details' => $bonusResults,
            ]);
        }

        if ($manual) {
            $this->logAuditEvent('leaderboard/manual_refresh', $actor, [
                'season_id' => (int) $season->id,
                'next_season_id' => (int) $newSeason->id,
            ]);
        }

        return [
            'new_season_id' => (int) $newSeason->id,
            'bonus_awarded' => $bonusAwarded,
            'bonuses' => $bonusResults,
        ];
    }

    private function rewardCycleAnchor(): Carbon
    {
        $firstStart = LeaderboardSeason::orderBy('start_at')->value('start_at');
        if ($firstStart) {
            return Carbon::parse($firstStart);
        }

        return now()->startOfDay();
    }

    private function normalizeItemTypeForSeason(string $itemType): string
    {
        $normalized = strtolower(trim($itemType));

        if (in_array($normalized, ['pet', 'plastic', 'pet/plastic battles', 'pet/plastic bottles', 'plastic_bottle', 'plastic bottle (pet)'], true)) {
            return 'pet';
        }

        if (in_array($normalized, ['aluminum/cans', 'aluminium/cans', 'aluminum cans', 'aluminium cans', 'aluminum_can', 'aluminum_cans', 'aluminium_can', 'aluminium_cans'], true)) {
            return 'aluminum_can';
        }

        if (in_array($normalized, ['tin/cans', 'tin cans', 'tin_can', 'tin_cans', 'steel (tin) can', 'steel_tin_can', 'metal', 'can', 'cans'], true)) {
            return 'tin_can';
        }

        return 'other';
    }

    private function estimateWeightKgForItemType(string $itemType, int $count): float
    {
        if ($count <= 0) {
            return 0.0;
        }

        return match ($itemType) {
            'pet' => ($count * self::PET_BOTTLE_WEIGHT_G) / 1000,
            'aluminum_can' => ($count * self::ALUMINUM_CAN_WEIGHT_G) / 1000,
            'tin_can' => ($count * self::TIN_CAN_WEIGHT_G) / 1000,
            default => 0.0,
        };
    }

    private function logAuditEvent(string $routePath, ?User $actor = null, array $payload = []): void
    {
        try {
            if (!Schema::hasTable('action_audit_logs')) {
                return;
            }

            ActionAuditLog::create([
                'actor_user_id' => $actor?->id,
                'actor_role_id' => $actor?->role_id,
                'actor_lgu_id' => $actor?->lgu_id,
                'http_method' => 'SYSTEM',
                'route_path' => $routePath,
                'route_name' => null,
                'controller_action' => static::class,
                'status_code' => 200,
                'ip_address' => null,
                'user_agent' => 'system',
                'query_params' => null,
                'payload' => $payload,
                'response_meta' => null,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to write leaderboard audit log', [
                'route_path' => $routePath,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
