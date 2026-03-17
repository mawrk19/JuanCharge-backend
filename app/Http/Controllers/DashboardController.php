<?php

namespace App\Http\Controllers;

use App\Models\ChargingSession;
use App\Models\Kiosk;
use App\Models\User;
use App\Models\Role;
use App\Models\RecyclingLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    /**
     * Get main dashboard overview
     */
    public function getOverview()
    {
        try {
            $user = auth()->user();

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
            } elseif ($user->isLguAdmin() || $user->isLguStaff()) {
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
            $activeKiosks = $kioskQuery->clone()->where('status', 'active')->count();
            $totalSessions = $sessionQuery->clone()->where('status', 'completed')->count();
            $activeSessions = $sessionQuery->clone()->where('status', 'active')->count();
            
            $totalEnergyKwh = $sessionQuery->clone()->where('status', 'completed')->sum('energy_wh') / 1000;
            $totalRecyclingKg = $recyclingQuery->clone()->sum('weight_kg');

            return response()->json([
                'success' => true,
                'data' => [
                    'total_users' => $totalUsers,
                    'kiosks' => ['total' => $totalKiosks, 'active' => $activeKiosks],
                    'charging' => ['total_sessions' => $totalSessions, 'active_sessions' => $activeSessions, 'total_energy_kwh' => round($totalEnergyKwh, 2)],
                    'recycling' => ['total_weight_kg' => round($totalRecyclingKg, 2)],
                    'co2_saved_kg' => round($totalEnergyKwh * 0.5, 2),
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
            $user = auth()->user();
            $limit = min($request->get('limit', 10), 30);

            $query = ChargingSession::with(['user:id,name', 'kiosk:id,kiosk_code,location'])
                ->orderBy('created_at', 'desc');

            if ($user->isKioskUser()) {
                $query->where('user_id', $user->id);
            } elseif ($user->isLguAdmin() || $user->isLguStaff()) {
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
            $user = auth()->user();
            $limit = min($request->get('limit', 10), 30);

            $query = RecyclingLog::with(['user:id,name', 'kiosk:id,kiosk_code'])
                ->orderBy('created_at', 'desc');

            if ($user->isKioskUser()) {
                $query->where('user_id', $user->id);
            } elseif ($user->isLguAdmin() || $user->isLguStaff()) {
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
            $user = auth()->user();
            $days = collect(range(6, 0))->map(function ($daysAgo) use ($user) {
                $date = now()->subDays($daysAgo);
                $sq = ChargingSession::whereDate('created_at', $date);
                $rq = RecyclingLog::whereDate('created_at', $date);

                if ($user->isKioskUser()) {
                    $sq->where('user_id', $user->id);
                    $rq->where('user_id', $user->id);
                } elseif ($user->isLguAdmin() || $user->isLguStaff()) {
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
}
