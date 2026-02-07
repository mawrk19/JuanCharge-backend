<?php

namespace App\Http\Controllers;

use App\Models\ChargingSession;
use App\Models\Kiosk;
use App\Models\KioskUser;
use App\Models\LguUser;
use App\Models\RecyclingLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    /**
     * Get main dashboard overview - clean, simple KPIs
     */
    public function getOverview()
    {
        try {
            $user = auth()->user();

            // Base Queries
            $userQuery = KioskUser::query();
            $kioskQuery = Kiosk::query();
            $sessionQuery = ChargingSession::query();
            $recyclingQuery = RecyclingLog::query();

            // Role-based filtering
            if ($user instanceof KioskUser) {
                $userQuery->where('id', $user->id);
                $sessionQuery->where('user_id', $user->id);
                $recyclingQuery->where('user_id', $user->id);
                // For patrons, we can show all kiosks or just available ones.
                // Leave kiosks unfiltered for now.
            } elseif ($user instanceof LguUser) {
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
                    $query->whereHas('chargingSessions.kiosk', function ($q) use ($lguId) {
                        $q->where('lgu_id', $lguId);
                    })->orWhereHas('recyclingLogs.kiosk', function ($q) use ($lguId) {
                        $q->where('lgu_id', $lguId);
                    });
                });
            }

            // Core Stats
            $totalUsers = $userQuery->count();
            $totalKiosks = $kioskQuery->count();
            $activeKiosks = $kioskQuery->clone()->where('status', 'active')->count();

            // Charging Stats
            $totalSessions = $sessionQuery->clone()->whereIn('status', ['completed', 'cancelled'])->count();
            $activeSessions = $sessionQuery->clone()->where('status', 'active')->count();
            $totalEnergyKwh = $sessionQuery->clone()->whereIn('status', ['completed', 'cancelled'])->sum('energy_wh') / 1000;

            // Recycling Stats (check if table exists)
            $totalRecyclingKg = 0;
            $totalRecyclingDeposits = 0;
            if (Schema::hasTable('recycling_logs')) {
                $totalRecyclingKg = $recyclingQuery->clone()->sum('weight_kg');
                $totalRecyclingDeposits = $recyclingQuery->clone()->count();
            }

            // Points Stats
            $totalPointsInCirculation = $userQuery->clone()->sum('points_balance');

            // Environmental Impact (0.5 kg CO2 per kWh)
            $co2Saved = $totalEnergyKwh * 0.5;

            return response()->json([
                'success' => true,
                'data' => [
                    'total_users' => $totalUsers,
                    'kiosks' => [
                        'total' => $totalKiosks,
                        'active' => $activeKiosks,
                    ],
                    'charging' => [
                        'total_sessions' => $totalSessions,
                        'active_sessions' => $activeSessions,
                        'total_energy_kwh' => round($totalEnergyKwh, 2),
                    ],
                    'recycling' => [
                        'total_weight_kg' => round($totalRecyclingKg, 2),
                        'total_deposits' => $totalRecyclingDeposits,
                    ],
                    'points_in_circulation' => $totalPointsInCirculation,
                    'co2_saved_kg' => round($co2Saved, 2),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Dashboard overview error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch dashboard overview: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get recent charging sessions
     */
    public function getRecentSessions(Request $request)
    {
        try {
            $user = auth()->user();
            $limit = min($request->get('limit', 10), 30);

            $query = ChargingSession::with(['user:id,name,first_name,last_name', 'kiosk:id,kiosk_code,location'])
                ->orderBy('created_at', 'desc');

            // Apply filters
            if ($user instanceof KioskUser) {
                $query->where('user_id', $user->id);
            } elseif ($user instanceof LguUser) {
                $lguId = $user->lgu_id;
                $query->whereHas('kiosk', function ($q) use ($lguId) {
                    $q->where('lgu_id', $lguId);
                });
            }

            $sessions = $query->limit($limit)
                ->get()
                ->map(function ($session) {
                    return [
                        'session_id' => $session->session_id,
                        'user_name' => $session->user->name ?? ($session->user->first_name . ' ' . $session->user->last_name),
                        'kiosk' => $session->kiosk->kiosk_code ?? 'N/A',
                        'location' => $session->kiosk->location ?? 'N/A',
                        'points_used' => $session->points_redeemed,
                        'duration_minutes' => $session->duration_minutes,
                        'status' => $session->status,
                        'created_at' => $session->created_at->toIso8601String(),
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $sessions
            ]);
        } catch (\Exception $e) {
            Log::error('Recent sessions error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch recent sessions'
            ], 500);
        }
    }

    /**
     * Get recent recycling deposits
     */
    public function getRecentRecycling(Request $request)
    {
        try {
            $user = auth()->user();
            // Check if table exists
            if (!Schema::hasTable('recycling_logs')) {
                return response()->json([
                    'success' => true,
                    'data' => []
                ]);
            }

            $limit = min($request->get('limit', 10), 30);

            $query = RecyclingLog::with(['user:id,name,first_name,last_name', 'kiosk:id,kiosk_code,location'])
                ->orderBy('created_at', 'desc');

            // Apply filters
            if ($user instanceof KioskUser) {
                $query->where('user_id', $user->id);
            } elseif ($user instanceof LguUser) {
                $lguId = $user->lgu_id;
                $query->whereHas('kiosk', function ($q) use ($lguId) {
                    $q->where('lgu_id', $lguId);
                });
            }

            $deposits = $query->limit($limit)
                ->get()
                ->map(function ($log) {
                    return [
                        'id' => $log->id,
                        'user_name' => $log->user->name ?? ($log->user->first_name . ' ' . $log->user->last_name),
                        'kiosk' => $log->kiosk->kiosk_code ?? 'N/A',
                        'weight_kg' => round($log->weight_kg, 2),
                        'points_earned' => $log->points_earned,
                        'item_type' => $log->item_type,
                        'created_at' => $log->created_at->toIso8601String(),
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $deposits
            ]);
        } catch (\Exception $e) {
            Log::error('Recent recycling error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch recent recycling: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get simple chart data - last 7 days
     */
    public function getChartData()
    {
        try {
            $user = auth()->user();
            $hasRecyclingTable = Schema::hasTable('recycling_logs');

            $days = collect(range(6, 0))->map(function ($daysAgo) use ($hasRecyclingTable, $user) {
                $date = now()->subDays($daysAgo);

                $sessionQuery = ChargingSession::whereDate('created_at', $date);
                $recyclingQuery = RecyclingLog::whereDate('created_at', $date);

                // Apply filters
                if ($user instanceof KioskUser) {
                    $sessionQuery->where('user_id', $user->id);
                    $recyclingQuery->where('user_id', $user->id);
                } elseif ($user instanceof LguUser) {
                    $lguId = $user->lgu_id;
                    $sessionQuery->whereHas('kiosk', function ($q) use ($lguId) {
                        $q->where('lgu_id', $lguId);
                    });
                    $recyclingQuery->whereHas('kiosk', function ($q) use ($lguId) {
                        $q->where('lgu_id', $lguId);
                    });
                }

                $recyclingKg = 0;
                if ($hasRecyclingTable) {
                    $recyclingKg = $recyclingQuery->sum('weight_kg');
                }

                return [
                    'date' => $date->format('M d'),
                    'sessions' => $sessionQuery->count(),
                    'recycling_kg' => round($recyclingKg, 2),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $days
            ]);
        } catch (\Exception $e) {
            Log::error('Chart data error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch chart data: ' . $e->getMessage()
            ], 500);
        }
    }
}
