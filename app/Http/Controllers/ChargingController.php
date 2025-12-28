<?php

namespace App\Http\Controllers;

use App\Models\ChargingSession;
use App\Models\PointsTransaction;
use App\Models\KioskUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ChargingController extends Controller
{
    // Constants for conversion formulas
    const MIN_POINTS = 10;
    const MAX_POINTS_PER_SESSION = 10000;
    const MINUTES_PER_POINT = 1;      // 1 point = 1 minute
    const WH_PER_MINUTE = 0.167;      // 10W port: 10W ÷ 60 min = 0.167 Wh/min

    /**
     * Redeem points to start a charging session
     */
    public function redeem(Request $request)
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $validated = $request->validate([
                'points' => 'required|integer|min:' . self::MIN_POINTS . '|max:' . self::MAX_POINTS_PER_SESSION,
                'kiosk_id' => 'nullable|string', // Changed from integer/exists to string to support MAC
            ]);

            $pointsToRedeem = $validated['points'];
            $kioskIdentifier = $validated['kiosk_id'] ?? null;
            $kioskId = null;

            if ($kioskIdentifier) {
                if (is_numeric($kioskIdentifier)) {
                    $kioskId = (int) $kioskIdentifier;
                } else if (str_starts_with($kioskIdentifier, 'kiosk-')) {
                    $macAddress = str_replace('kiosk-', '', $kioskIdentifier);
                    $kiosk = \App\Models\Kiosk::where('mac_address', $macAddress)->first();
                    if ($kiosk) {
                        $kioskId = $kiosk->id;
                    } else {
                        return response()->json([
                            'success' => false,
                            'message' => 'Kiosk not found with the provided identifier'
                        ], 404);
                    }
                } else {
                    // Try direct ID if it's just a number string but not pure numeric (though is_numeric handles most)
                    $kioskId = (int) $kioskIdentifier;
                }
            }

            // Check for active session
            $activeSession = ChargingSession::where('user_id', $user->id)
                ->where('status', 'active')
                ->first();

            if ($activeSession) {
                if ($activeSession->isExpired()) {
                    // Automatically mark as completed if it's expired but still shown as active
                    $activeSession->status = 'completed';
                    $activeSession->completed_at = $activeSession->end_time;
                    $activeSession->save();
                    $activeSession = null; // Clear it so we can proceed
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'You already have an active charging session',
                        'data' => [
                            'active_session_id' => $activeSession->session_id,
                            'remaining_time_minutes' => $activeSession->remaining_minutes
                        ]
                    ], 400);
                }
            }

            // Check sufficient balance
            if ($user->points_balance < $pointsToRedeem) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient points balance',
                    'data' => [
                        'required' => $pointsToRedeem,
                        'available' => $user->points_balance
                    ]
                ], 400);
            }

            DB::beginTransaction();

            try {
                // Calculate energy and duration
                // 1 point = 1 minute, 1 minute = 0.167 Wh (10W port)
                $durationMinutes = $pointsToRedeem * self::MINUTES_PER_POINT;
                $energyWh = $durationMinutes * self::WH_PER_MINUTE;

                // Generate unique session ID
                $sessionId = 'SESSION_' . time() . '_' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));

                // Calculate start and end time
                $startTime = now();
                $endTime = now()->addMinutes($durationMinutes);

                // Create charging session
                $session = ChargingSession::create([
                    'session_id' => $sessionId,
                    'user_id' => $user->id,
                    'kiosk_id' => $kioskId,
                    'points_redeemed' => $pointsToRedeem,
                    'energy_wh' => $energyWh,
                    'duration_minutes' => $durationMinutes,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'status' => 'active',
                ]);

                // Deduct points from user balance
                $user->points_balance -= $pointsToRedeem;
                $user->save();

                // Create points transaction record
                PointsTransaction::create([
                    'user_id' => $user->id,
                    'transaction_type' => 'redeemed',
                    'points' => -$pointsToRedeem,
                    'balance_after' => $user->points_balance,
                    'reference_type' => 'charging_session',
                    'reference_id' => $sessionId,
                    'description' => 'Redeemed for charging session',
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Charging session started successfully',
                    'data' => [
                        'session' => [
                            'session_id' => $session->session_id,
                            'points_redeemed' => $session->points_redeemed,
                            'energy_wh' => (float) $session->energy_wh,
                            'duration_minutes' => $session->duration_minutes,
                            'start_time' => $session->start_time->toIso8601String(),
                            'end_time' => $session->end_time->toIso8601String(),
                            'status' => $session->status,
                        ],
                        'updated_balance' => $user->points_balance
                    ]
                ], 201);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to redeem points: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to start charging session: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get active charging session
     */
    public function getActive()
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $session = ChargingSession::where('user_id', $user->id)
                ->where('status', 'active')
                ->first();

            if (!$session) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'session' => null
                    ]
                ], 200);
            }

            // Check if it's expired
            if ($session->isExpired()) {
                $session->status = 'completed';
                $session->completed_at = $session->end_time;
                $session->save();
                
                return response()->json([
                    'success' => true,
                    'data' => [
                        'session' => null
                    ]
                ], 200);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'session' => [
                        'session_id' => $session->session_id,
                        'points_redeemed' => $session->points_redeemed,
                        'energy_wh' => (float) $session->energy_wh,
                        'duration_minutes' => $session->duration_minutes,
                        'start_time' => $session->start_time->toIso8601String(),
                        'end_time' => $session->end_time->toIso8601String(),
                        'status' => $session->status,
                        'remaining_minutes' => $session->remaining_minutes
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to get active session: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve active session: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel an active charging session
     */
    public function cancel(Request $request)
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $validated = $request->validate([
                'session_id' => 'required|string',
            ]);

            $session = ChargingSession::where('session_id', $validated['session_id'])
                ->where('user_id', $user->id)
                ->first();

            if (!$session) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session not found'
                ], 404);
            }

            if ($session->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Session is not active'
                ], 400);
            }

            if ($session->isExpired()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session has already expired'
                ], 400);
            }

            // Calculate time used and forfeited
            $now = now();
            $timeUsedMinutes = $session->start_time->diffInMinutes($now);
            $timeForfeitedMinutes = $session->duration_minutes - $timeUsedMinutes;

            // 1 minute = 0.167 Wh (10W port)
            $energyUsedWh = $timeUsedMinutes * self::WH_PER_MINUTE;
            $energyForfeitedWh = $session->energy_wh - $energyUsedWh;

            // Update session status
            $session->status = 'cancelled';
            $session->cancelled_at = $now;
            $session->save();

            return response()->json([
                'success' => true,
                'message' => 'Charging session cancelled',
                'data' => [
                    'session_id' => $session->session_id,
                    'time_used_minutes' => $timeUsedMinutes,
                    'time_forfeited_minutes' => max(0, $timeForfeitedMinutes),
                    'energy_used_wh' => round($energyUsedWh, 2),
                    'energy_forfeited_wh' => round(max(0, $energyForfeitedWh), 2)
                ]
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to cancel session: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel session: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get charging session history
     */
    public function history(Request $request)
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $perPage = min($request->get('per_page', 10), 50);
            $status = $request->get('status');

            $query = ChargingSession::with('kiosk')
                ->where('user_id', $user->id)
                ->orderBy('created_at', 'desc');

            if ($status && in_array($status, ['active', 'completed', 'cancelled'])) {
                $query->where('status', $status);
            }

            $sessions = $query->paginate($perPage);

            $data = $sessions->map(function ($session) {
                return [
                    'session_id' => $session->session_id,
                    'points_redeemed' => $session->points_redeemed,
                    'energy_wh' => (float) $session->energy_wh,
                    'duration_minutes' => $session->duration_minutes,
                    'start_time' => $session->start_time->toIso8601String(),
                    'end_time' => $session->end_time->toIso8601String(),
                    'status' => $session->status,
                    'completed_at' => $session->completed_at ? $session->completed_at->toIso8601String() : null,
                    'cancelled_at' => $session->cancelled_at ? $session->cancelled_at->toIso8601String() : null,
                    'kiosk' => $session->kiosk ? [
                        'id' => $session->kiosk->id,
                        'kiosk_code' => $session->kiosk->kiosk_code,
                        'location' => $session->kiosk->location,
                    ] : null,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data,
                'pagination' => [
                    'current_page' => $sessions->currentPage(),
                    'last_page' => $sessions->lastPage(),
                    'per_page' => $sessions->perPage(),
                    'total' => $sessions->total(),
                    'from' => $sessions->firstItem(),
                    'to' => $sessions->lastItem(),
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to get session history: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve session history: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get points balance
     */
    public function getBalance()
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $totalEarned = PointsTransaction::where('user_id', $user->id)
                ->where('points', '>', 0)
                ->sum('points');

            $totalRedeemed = abs(PointsTransaction::where('user_id', $user->id)
                ->where('points', '<', 0)
                ->sum('points'));

            // Calculate available energy: points × minutes × Wh per minute
            $availableMinutes = $user->points_balance * self::MINUTES_PER_POINT;
            $availableEnergyWh = $availableMinutes * self::WH_PER_MINUTE;

            return response()->json([
                'success' => true,
                'data' => [
                    'points_balance' => $user->points_balance,
                    'total_earned' => $totalEarned,
                    'total_redeemed' => $totalRedeemed,
                    'available_energy_wh' => round($availableEnergyWh, 2)
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to get points balance: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve points balance: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get points transaction history
     */
    public function transactions(Request $request)
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $perPage = min($request->get('per_page', 20), 50);
            $type = $request->get('type');

            $query = PointsTransaction::where('user_id', $user->id)
                ->orderBy('created_at', 'desc');

            if ($type && in_array($type, ['earned', 'redeemed', 'bonus', 'adjustment'])) {
                $query->where('transaction_type', $type);
            }

            $transactions = $query->paginate($perPage);

            $data = $transactions->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'transaction_type' => $transaction->transaction_type,
                    'points' => $transaction->points,
                    'balance_after' => $transaction->balance_after,
                    'reference_type' => $transaction->reference_type,
                    'reference_id' => $transaction->reference_id,
                    'description' => $transaction->description,
                    'created_at' => $transaction->created_at->toIso8601String(),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data,
                'pagination' => [
                    'current_page' => $transactions->currentPage(),
                    'last_page' => $transactions->lastPage(),
                    'per_page' => $transactions->perPage(),
                    'total' => $transactions->total(),
                    'from' => $transactions->firstItem(),
                    'to' => $transactions->lastItem(),
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to get transactions: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve transactions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get dashboard statistics
     */
    public function getDashboardStats()
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            // Get total charging sessions (completed and cancelled count as charges)
            $totalCharges = ChargingSession::where('user_id', $user->id)
                ->whereIn('status', ['completed', 'cancelled'])
                ->count();

            // Get total energy used (from both completed AND cancelled sessions, convert Wh to kWh)
            $totalEnergyWh = ChargingSession::where('user_id', $user->id)
                ->whereIn('status', ['completed', 'cancelled'])
                ->sum('energy_wh');
            $totalEnergyKwh = $totalEnergyWh / 1000; // Convert Wh to kWh

            // Calculate CO2 saved (approximately 0.5 kg CO2 per kWh)
            $co2Saved = $totalEnergyKwh * 0.5;

            // Get recyclables weight
            $totalRecyclablesWeight = $user->total_recyclables_weight ?? 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'total_points' => (int) $user->points_balance,
                    'total_charges' => (int) $totalCharges,
                    'total_recyclables_weight_kg' => round((float) $totalRecyclablesWeight, 2),
                    'co2_saved_kg' => round((float) $co2Saved, 2),
                    // Adding energy for backward compatibility if needed, but primary structure matches request
                    'energy_used_kwh' => round($totalEnergyKwh, 2),
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to get dashboard stats: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve dashboard stats: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Community Leaderboard
     */
    public function getLeaderboard()
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
            }

            // Get top 10 users ranked by total points earned
            // Note: KioskUser needs to have points_total for this to be effective
            $rankings = KioskUser::orderBy('points_total', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($u, $index) {
                    return [
                        'rank' => $index + 1,
                        'name' => $u->name ?: 'Juan Dela Cruz', // Fallback name
                        'recycled' => (float) ($u->total_recyclables_weight ?? 0),
                        'points' => (int) ($u->points_total ?? 0),
                        'bonus' => 0 // Future: implement bonus points logic
                    ];
                });

            // Calculate current user's rank
            // rank = 1 + number of users with more points
            $userRank = KioskUser::where('points_total', '>', $user->points_total)->count() + 1;

            // Optional: percent to next rank (simplified logic: progress towards top 10 if not in it)
            $percentToRank = 0;
            if ($userRank > 10) {
                $top10Threshold = KioskUser::orderBy('points_total', 'desc')->skip(9)->take(1)->value('points_total') ?? 1000;
                $percentToRank = min(100, round(($user->points_total / max(1, $top10Threshold)) * 100));
            } else {
                $percentToRank = 100;
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'rankings' => $rankings,
                    'user_stats' => [
                        'rank' => $userRank,
                        'name' => 'You',
                        'points' => (int) $user->points_total,
                        'recycled_count' => (float) ($user->total_recyclables_weight ?? 0),
                        'percent_to_rank' => $percentToRank
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Leaderboard error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to fetch leaderboard'], 500);
        }
    }

    /**
     * Get Dynamic Achievements
     */
    public function getAchievements()
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
            }

            $totalCharges = ChargingSession::where('user_id', $user->id)
                ->whereIn('status', ['completed', 'cancelled'])
                ->count();

            $totalRecycled = (float) ($user->total_recyclables_weight ?? 0);
            $pointsTotal = (int) ($user->points_total ?? 0);

            $achievements = [
                [
                    'id' => 'first_charge',
                    'title' => 'First Charge',
                    'is_completed' => $totalCharges >= 1,
                    'progress' => min(1, $totalCharges),
                    'target' => 1,
                    'points_reward' => 10
                ],
                [
                    'id' => 'eco_warrior',
                    'title' => 'Eco Warrior',
                    'is_completed' => $totalRecycled >= 50,
                    'progress' => round($totalRecycled, 1),
                    'target' => 50,
                    'points_reward' => 50
                ],
                [
                    'id' => 'juice_up',
                    'title' => 'Juice Up',
                    'is_completed' => $totalCharges >= 10,
                    'progress' => $totalCharges,
                    'target' => 10,
                    'points_reward' => 25
                ],
                [
                    'id' => 'point_collector',
                    'title' => 'Point Collector',
                    'is_completed' => $pointsTotal >= 1000,
                    'progress' => $pointsTotal,
                    'target' => 1000,
                    'points_reward' => 100
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $achievements
            ]);

        } catch (\Exception $e) {
            Log::error('Achievements error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to fetch achievements'], 500);
        }
    }
}
