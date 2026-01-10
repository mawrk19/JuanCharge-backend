<?php

namespace App\Http\Controllers;

use App\Models\ChargingSession;
use App\Models\Kiosk;
use App\Models\KioskUser;
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
            // Core Stats
            $totalUsers = KioskUser::count();
            $totalKiosks = Kiosk::count();
            $activeKiosks = Kiosk::where('status', 'active')->count();

            // Charging Stats
            $totalSessions = ChargingSession::whereIn('status', ['completed', 'cancelled'])->count();
            $activeSessions = ChargingSession::where('status', 'active')->count();
            $totalEnergyKwh = ChargingSession::whereIn('status', ['completed', 'cancelled'])->sum('energy_wh') / 1000;

            // Recycling Stats (check if table exists)
            $totalRecyclingKg = 0;
            $totalRecyclingDeposits = 0;
            if (Schema::hasTable('recycling_logs')) {
                $totalRecyclingKg = RecyclingLog::sum('weight_kg');
                $totalRecyclingDeposits = RecyclingLog::count();
            }

            // Points Stats
            $totalPointsInCirculation = KioskUser::sum('points_balance');

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
            $limit = min($request->get('limit', 10), 30);

            $sessions = ChargingSession::with(['user:id,name,first_name,last_name', 'kiosk:id,kiosk_code,location'])
                ->orderBy('created_at', 'desc')
                ->limit($limit)
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
            // Check if table exists
            if (!Schema::hasTable('recycling_logs')) {
                return response()->json([
                    'success' => true,
                    'data' => []
                ]);
            }

            $limit = min($request->get('limit', 10), 30);

            $deposits = RecyclingLog::with(['user:id,name,first_name,last_name', 'kiosk:id,kiosk_code,location'])
                ->orderBy('created_at', 'desc')
                ->limit($limit)
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
            $hasRecyclingTable = Schema::hasTable('recycling_logs');

            $days = collect(range(6, 0))->map(function ($daysAgo) use ($hasRecyclingTable) {
                $date = now()->subDays($daysAgo);
                
                $recyclingKg = 0;
                if ($hasRecyclingTable) {
                    $recyclingKg = RecyclingLog::whereDate('created_at', $date)->sum('weight_kg');
                }

                return [
                    'date' => $date->format('M d'),
                    'sessions' => ChargingSession::whereDate('created_at', $date)->count(),
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
