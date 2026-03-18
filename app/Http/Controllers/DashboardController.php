<?php

namespace App\Http\Controllers;

use App\Models\ChargingSession;
use App\Models\Kiosk;
use App\Models\User;
use App\Models\Role;
use App\Models\RecyclingLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    private const KIOSK_ONLINE_THRESHOLD_SECONDS = 30;
    private const PET_BOTTLE_WEIGHT_G = 10.5;      // 9g - 12g midpoint
    private const ALUMINUM_CAN_WEIGHT_G = 14.0;    // 13g - 15g midpoint
    private const TIN_CAN_WEIGHT_G = 42.5;         // 35g - 50g midpoint

    /**
     * Get main dashboard overview
     */
    public function getOverview()
    {
        try {
            $user = $this->authenticatedUser();

            // Base Queries
            $userQuery = User::where('role_id', Role::KIOSK_USER);
            $kioskQuery = Kiosk::query();
            $sessionQuery = ChargingSession::query();
            $recyclingQuery = RecyclingLog::query();

            // Role-based filtering
            if ($user->isKioskUser()) {
                $userQuery->where('id', $user->id);
                $sessionQuery->where('user_id', $user->id);
                $recyclingQuery->where('user_id', $user->id);
            } elseif ($user->isLguRole()) {
                $lguId = $user->lgu_id;
                $kioskQuery->where('lgu_id', $lguId);
                $sessionQuery->whereHas('kiosk', function ($q) use ($lguId) {
                    $q->where('lgu_id', $lguId);
                });
                $recyclingQuery->whereHas('kiosk', function ($q) use ($lguId) {
                    $q->where('lgu_id', $lguId);
                });
                // Filter users to only those who have used kiosks in this LGU
                $userQuery->where(function ($query) use ($lguId) {
                    $query->whereHas('sessions.kiosk', function ($q) use ($lguId) {
                        $q->where('lgu_id', $lguId);
                    });
                });
            }

            // Stats
            $totalUsers = $userQuery->count();
            $totalKiosks = $kioskQuery->count();
            $activeKiosks = $kioskQuery->clone()
                ->whereNotNull('last_active')
                ->where('last_active', '>=', now()->subSeconds(self::KIOSK_ONLINE_THRESHOLD_SECONDS))
                ->count();

            $totalSessions = $sessionQuery->clone()->where('status', 'completed')->count();
            $activeSessions = $sessionQuery->clone()->where('status', 'active')->count();

            $completedEnergyKwh = $sessionQuery->clone()->where('status', 'completed')->sum('energy_wh') / 1000;
            $activeEnergyKwh = $sessionQuery->clone()->where('status', 'active')->sum('energy_wh') / 1000;

            [$estimatedRecyclingKg, $accumulatedPoints] = $this->calculateRecyclingWeightAndPoints($recyclingQuery->clone());

            return response()->json([
                'success' => true,
                'data' => [
                    'total_users' => $totalUsers,
                    'kiosks' => [
                        'total' => $totalKiosks,
                        'active' => $activeKiosks,
                        'online_threshold_seconds' => self::KIOSK_ONLINE_THRESHOLD_SECONDS,
                    ],
                    'charging' => [
                        'total_sessions' => $totalSessions,
                        'active_sessions' => $activeSessions,
                        'total_energy_kwh' => round($completedEnergyKwh, 2),
                        'active_energy_kwh' => round($activeEnergyKwh, 2),
                    ],
                    'recycling' => [
                        'total_weight_kg' => round($estimatedRecyclingKg, 2),
                        'accumulated_points' => (int) $accumulatedPoints,
                    ],
                    'co2_saved_kg' => round(($completedEnergyKwh + $activeEnergyKwh) * 0.5, 2),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Dashboard overview error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to fetch dashboard'], 500);
        }
    }

    public function getRecentSessions(Request $request)
    {
        try {
            $user = $this->authenticatedUser();
            $limit = min($request->get('limit', 10), 30);

            $query = ChargingSession::with(['user:id,name', 'kiosk:id,kiosk_code,location'])
                ->orderBy('created_at', 'desc');

            if ($user->isKioskUser()) {
                $query->where('user_id', $user->id);
            } elseif ($user->isLguRole()) {
                $lguId = $user->lgu_id;
                $query->whereHas('kiosk', function ($q) use ($lguId) {
                    $q->where('lgu_id', $lguId);
                });
            }

            return response()->json(['success' => true, 'data' => $query->limit($limit)->get()]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error'], 500);
        }
    }

    public function getRecentRecycling(Request $request)
    {
        try {
            $user = $this->authenticatedUser();
            $limit = min($request->get('limit', 10), 30);

            $query = RecyclingLog::with(['user:id,name', 'kiosk:id,kiosk_code'])
                ->orderBy('created_at', 'desc');

            if ($user->isKioskUser()) {
                $query->where('user_id', $user->id);
            } elseif ($user->isLguRole()) {
                $lguId = $user->lgu_id;
                $query->whereHas('kiosk', function ($q) use ($lguId) {
                    $q->where('lgu_id', $lguId);
                });
            }

            return response()->json(['success' => true, 'data' => $query->limit($limit)->get()]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error'], 500);
        }
    }

    public function getChartData()
    {
        try {
            $user = $this->authenticatedUser();
            $days = collect(range(6, 0))->map(function ($daysAgo) use ($user) {
                $date = now()->subDays($daysAgo);
                $sq = ChargingSession::whereDate('created_at', $date);
                $rq = RecyclingLog::whereDate('created_at', $date);

                if ($user->isKioskUser()) {
                    $sq->where('user_id', $user->id);
                    $rq->where('user_id', $user->id);
                } elseif ($user->isLguRole()) {
                    $lguId = $user->lgu_id;
                    $sq->whereHas('kiosk', function ($q) use ($lguId) { $q->where('lgu_id', $lguId); });
                    $rq->whereHas('kiosk', function ($q) use ($lguId) { $q->where('lgu_id', $lguId); });
                }

                return [
                    'date' => $date->format('M d'),
                    'sessions' => $sq->count(),
                    'recycling_kg' => round($rq->sum('weight_kg'), 2),
                ];
            });

            return response()->json(['success' => true, 'data' => $days]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error'], 500);
        }
    }

    private function authenticatedUser(): User
    {
        /** @var User|null $user */
        $user = auth()->user();

        abort_if(!$user, 401, 'Unauthenticated.');

        return $user;
    }

    /**
     * Compute recycling totals by combining measured weight_kg and count-based estimates.
     */
    private function calculateRecyclingWeightAndPoints($recyclingQuery): array
    {
        $rows = $recyclingQuery
            ->selectRaw('item_type, COALESCE(SUM(weight_kg), 0) as total_weight_kg, COALESCE(SUM(`count`), 0) as total_count')
            ->groupBy('item_type')
            ->get();

        $totalWeightKg = 0.0;
        $totalPoints = 0;

        foreach ($rows as $row) {
            $itemType = $this->normalizeDashboardItemType((string) ($row->item_type ?? ''));
            $totalCount = (int) ($row->total_count ?? 0);
            $measuredWeightKg = (float) ($row->total_weight_kg ?? 0);

            // Prefer measured weights when present; use count-based estimate otherwise.
            if ($measuredWeightKg > 0) {
                $totalWeightKg += $measuredWeightKg;
            } else {
                $totalWeightKg += $this->estimateWeightKgForItemType($itemType, $totalCount);
            }

            $totalPoints += $this->estimatePointsForItemType($itemType, $totalCount);
        }

        return [$totalWeightKg, $totalPoints];
    }

    private function normalizeDashboardItemType(string $itemType): string
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

    private function estimatePointsForItemType(string $itemType, int $count): int
    {
        if ($count <= 0) {
            return 0;
        }

        return match ($itemType) {
            'pet' => $count,
            'aluminum_can', 'tin_can' => $count * 2,
            default => 0,
        };
    }
}
