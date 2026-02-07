<?php

namespace App\Http\Controllers;

use App\Models\ChargingSession;
use App\Models\KioskUser;
use App\Models\PointsTransaction;
use App\Models\RecyclingLog;
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
                'kiosk_code' => 'required|string', // Changed from kiosk_id to kiosk_code
                'port_number' => 'required|integer|min:1|max:3',
                'session_id' => 'nullable|string',
            ]);

            $pointsToRedeem = $validated['points'];
            $kioskIdentifier = $validated['kiosk_code'];
            $kioskId = null;

            $kiosk = \App\Models\Kiosk::where('kiosk_code', $kioskIdentifier)
                ->orWhere('id', is_numeric($kioskIdentifier) ? $kioskIdentifier : -1)
                ->orWhere('mac_address', str_replace('kiosk-', '', $kioskIdentifier))
                ->first();

            if (!$kiosk) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kiosk not found'
                ], 404);
            }

            $kioskId = $kiosk->id;

            // --- START HANDSHAKE VALIDATION ---
            // 1. Connectivity Check (last_active < 30 seconds)
            $lastActive = $kiosk->last_active;
            if (!$lastActive || $lastActive->diffInSeconds(now()) > 30) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kiosk Offline',
                    'error_code' => 'KIOSK_OFFLINE'
                ], 403);
            }

            // 2. Port Status Check
            $portNumber = $validated['port_number'];
            $ports = $kiosk->details['ports'] ?? [];
            $portStatus = 'unknown';
            foreach ($ports as $port) {
                if (isset($port['port']) && $port['port'] == $portNumber) {
                    $portStatus = $port['status'] ?? 'unknown';
                    break;
                }
            }

            if ($portStatus === 'active' || $portStatus === 'busy') {
                return response()->json([
                    'success' => false,
                    'message' => 'Port Busy',
                    'error_code' => 'PORT_BUSY'
                ], 400);
            }
            // --- END HANDSHAKE VALIDATION ---

            $requestedSessionId = $validated['session_id'] ?? null;

            // Check for active session
            $activeSession = ChargingSession::where('user_id', $user->id)
                ->where('status', 'active')
                ->first();

            // Handle session extension if session_id is provided
            if ($requestedSessionId) {
                // Verify the session exists and belongs to the user
                if (!$activeSession || $activeSession->session_id !== $requestedSessionId) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid session ID or session not found'
                    ], 404);
                }

                // Check if session is expired
                if ($activeSession->isExpired()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot extend an expired session'
                    ], 400);
                }

                // Check sufficient balance for extension
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

                // Perform session extension
                DB::beginTransaction();

                try {
                    // Calculate additional time and energy
                    $additionalMinutes = $pointsToRedeem * self::MINUTES_PER_POINT;
                    $additionalEnergyWh = $additionalMinutes * self::WH_PER_MINUTE;

                    // Update session
                    $activeSession->duration_minutes += $additionalMinutes;
                    $activeSession->energy_wh += $additionalEnergyWh;
                    $activeSession->points_redeemed += $pointsToRedeem;
                    $activeSession->end_time = $activeSession->end_time->addMinutes($additionalMinutes);
                    $activeSession->save();

                    // Deduct points from user balance
                    $user->points_balance -= $pointsToRedeem;
                    $user->save();

                    // Create points transaction record
                    PointsTransaction::create([
                        'user_id' => $user->id,
                        'transaction_type' => 'redeemed',
                        'type' => 'charge',
                        'title' => 'Extended Session',
                        'points' => -$pointsToRedeem,
                        'balance_after' => $user->points_balance,
                        'status' => 'completed',
                        'reference_type' => 'charging_session',
                        'reference_id' => $activeSession->session_id,
                        'description' => 'Extended charging session',
                    ]);

                    DB::commit();

                    return response()->json([
                        'success' => true,
                        'message' => 'Charging session extended successfully',
                        'data' => [
                            'session' => [
                                'session_id' => $activeSession->session_id,
                                'points_redeemed' => $activeSession->points_redeemed,
                                'energy_wh' => (float) $activeSession->energy_wh,
                                'duration_minutes' => $activeSession->duration_minutes,
                                'start_time' => $activeSession->start_time->toIso8601String(),
                                'end_time' => $activeSession->end_time->toIso8601String(),
                                'status' => $activeSession->status,
                                'remaining_minutes' => $activeSession->remaining_minutes
                            ],
                            'updated_balance' => $user->points_balance,
                            'extended_by_minutes' => $additionalMinutes
                        ]
                    ], 200);

                } catch (\Exception $e) {
                    DB::rollBack();
                    throw $e;
                }
            }

            // Original logic: Check for active session when NOT extending
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
                    'port_number' => $validated['port_number'],
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
                    'type' => 'charge',
                    'title' => 'New Charging Session',
                    'points' => -$pointsToRedeem,
                    'balance_after' => $user->points_balance,
                    'status' => 'completed',
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

            $data = $sessions->getCollection()->map(function ($session) {
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

            $perPage = min($request->get('limit', $request->get('per_page', 20)), 50);
            $type = $request->get('type');

            $query = PointsTransaction::where('user_id', $user->id)
                ->orderBy('created_at', 'desc');

            if ($type && in_array($type, ['earned', 'redeemed', 'bonus', 'adjustment'])) {
                $query->where('transaction_type', $type);
            }

            $transactions = $query->paginate($perPage);

            $data = $transactions->getCollection()->map(function ($transaction) {
                return [
                    'id' => 'TXN_' . $transaction->id,
                    'type' => $transaction->type ?? 'other',
                    'title' => $transaction->title ?? $transaction->description,
                    'amount' => abs($transaction->points),
                    'transaction_type' => $transaction->points > 0 ? 'credit' : 'debit',
                    'status' => $transaction->status ?? 'completed',
                    'points_before' => $transaction->balance_after - $transaction->points,
                    'points_after' => $transaction->balance_after,
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

            // Safeguard: Ensure points_total is at least as much as balance
            if (($user instanceof \App\Models\KioskUser) && ($user->points_balance > $user->points_total)) {
                $user->points_total = $user->points_balance;
                $user->save();
            }

            // Get recyclables weight
            $totalRecyclablesWeight = $user->total_recyclables_weight ?? 0;

            // Calculate rank if user is a patron
            $rank = '-';
            if ($user instanceof \App\Models\KioskUser) {
                $rank = \App\Models\KioskUser::where('points_total', '>', $user->points_total)->count() + 1;
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'total_points' => (int) $user->points_balance,
                    'total_charges' => (int) $totalCharges,
                    'total_recyclables_weight_kg' => round((float) $totalRecyclablesWeight, 2),
                    'co2_saved_kg' => round((float) $co2Saved, 2),
                    'rank' => $rank,
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
    public function getLeaderboard(Request $request)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
            }

            $period = $request->query('period', 'all'); // all, monthly, weekly
            $startDate = null;

            if ($period === 'monthly') {
                $startDate = now()->startOfMonth();
            } elseif ($period === 'weekly') {
                $startDate = now()->startOfWeek();
            }

            // Base query for rankings
            if ($startDate) {
                // Get earnings from transactions in this period
                $earnings = PointsTransaction::where('transaction_type', 'earned')
                    ->where('created_at', '>=', $startDate)
                    ->select('user_id', DB::raw('SUM(points) as period_points'))
                    ->groupBy('user_id');

                // Get weight from recycling logs in this period
                $weights = RecyclingLog::where('created_at', '>=', $startDate)
                    ->select('user_id', DB::raw('SUM(weight_kg) as period_weight'))
                    ->groupBy('user_id');

                // Combine rankings
                $rankingsRaw = KioskUser::leftJoinSub($earnings, 'earnings', function ($join) {
                    $join->on('kiosk_users.id', '=', 'earnings.user_id');
                })
                    ->leftJoinSub($weights, 'weights', function ($join) {
                        $join->on('kiosk_users.id', '=', 'weights.user_id');
                    })
                    ->where(function ($q) {
                        $q->whereNotNull('earnings.period_points')
                            ->orWhereNotNull('weights.period_weight');
                    })
                    ->select('kiosk_users.*', 'earnings.period_points', 'weights.period_weight')
                    ->orderBy('earnings.period_points', 'desc')
                    ->limit(10)
                    ->get();

                $rankings = $rankingsRaw->map(function ($u, $index) {
                    return [
                        'rank' => $index + 1,
                        'name' => $u->name ?: 'Juan Dela Cruz',
                        'recycled' => (float) ($u->period_weight ?? 0),
                        'points' => (int) ($u->period_points ?? 0),
                        'bonus' => 0
                    ];
                });

                // User Specific Stats for Period
                $userPeriodPoints = PointsTransaction::where('user_id', $user->id)
                    ->where('transaction_type', 'earned')
                    ->where('created_at', '>=', $startDate)
                    ->sum('points');

                $userPeriodWeight = RecyclingLog::where('user_id', $user->id)
                    ->where('created_at', '>=', $startDate)
                    ->sum('weight_kg');

                // Rank calculation for period
                $betterUsersCount = PointsTransaction::where('transaction_type', 'earned')
                    ->where('created_at', '>=', $startDate)
                    ->select('user_id', DB::raw('SUM(points) as period_points'))
                    ->groupBy('user_id')
                    ->having('period_points', '>', $userPeriodPoints)
                    ->get()
                    ->count();

                $userRank = $betterUsersCount + 1;
                $userPoints = (int) $userPeriodPoints;
                $userRecycled = (float) $userPeriodWeight;

            } else {
                // All-time logic
                // Safeguard: Ensure points_total is at least equal to points_balance
                if ($user->points_balance > $user->points_total) {
                    $user->points_total = $user->points_balance;
                    $user->save();
                }

                $rankings = KioskUser::orderBy('points_total', 'desc')
                    ->limit(10)
                    ->get()
                    ->map(function ($u, $index) {
                        return [
                            'rank' => $index + 1,
                            'name' => $u->name ?: 'Juan Dela Cruz',
                            'recycled' => (float) ($u->total_recyclables_weight ?? 0),
                            'points' => (int) ($u->points_total ?? 0),
                            'bonus' => 0
                        ];
                    });

                $userRank = KioskUser::where('points_total', '>', $user->points_total)->count() + 1;
                $userPoints = (int) $user->points_total;
                $userRecycled = (float) ($user->total_recyclables_weight ?? 0);
            }

            // Calculate percent to rank (Simplified)
            $percentToRank = 0;
            if ($userRank > 10) {
                $thresholdQuery = $startDate
                    ? PointsTransaction::where('transaction_type', 'earned')->where('created_at', '>=', $startDate)->select(DB::raw('SUM(points) as p'))->groupBy('user_id')->orderBy('p', 'desc')
                    : KioskUser::orderBy('points_total', 'desc')->select('points_total as p');

                $top10Threshold = $thresholdQuery->skip(9)->take(1)->value('p') ?? 100;
                $percentToRank = min(100, round(($userPoints / max(1, $top10Threshold)) * 100));
            } else {
                $percentToRank = 100;
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'period' => $period,
                    'rankings' => $rankings,
                    'user_stats' => [
                        'rank' => $userRank,
                        'name' => 'You',
                        'points' => $userPoints,
                        'recycled_count' => $userRecycled,
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

    /**
     * Record a recycling deposit and award points
     */
    public function depositRecyclables(Request $request)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
            }

            $validated = $request->validate([
                'weight_kg' => 'required|numeric|min:0.1',
                'kiosk_id' => 'nullable|exists:kiosks,id',
                'item_type' => 'nullable|string',
            ]);

            // Simple formula: 1kg = 15 points
            $pointsToEarn = ceil($validated['weight_kg'] * 15);

            DB::beginTransaction();
            try {
                // Create recycling log
                $log = RecyclingLog::create([
                    'user_id' => $user->id,
                    'kiosk_id' => $request->kiosk_id,
                    'weight_kg' => $validated['weight_kg'],
                    'points_earned' => $pointsToEarn,
                    'item_type' => $validated['item_type'] ?? 'mixed',
                    'status' => 'completed',
                ]);

                // Update user points and stats
                $user->points_balance += $pointsToEarn;
                $user->points_total += $pointsToEarn;
                $user->total_recyclables_weight += $validated['weight_kg'];
                $user->save();

                // Create transaction history record
                PointsTransaction::create([
                    'user_id' => $user->id,
                    'transaction_type' => 'earned',
                    'type' => 'recycling',
                    'title' => 'Recycling Deposit',
                    'points' => $pointsToEarn,
                    'balance_after' => $user->points_balance,
                    'status' => 'completed',
                    'reference_type' => 'recycling_log',
                    'reference_id' => $log->id,
                    'description' => "Earned $pointsToEarn points for {$validated['weight_kg']}kg of recyclables",
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Recyclables deposited successfully!',
                    'data' => [
                        'points_earned' => $pointsToEarn,
                        'new_balance' => $user->points_balance,
                        'total_weight_kg' => $user->total_recyclables_weight
                    ]
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Recycling deposit error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to process recycling deposit: ' . $e->getMessage()
            ], 500);
        }
    }
    /**
     * Redeem points from Kiosk (Server-side validation)
     */
    public function redeemFromKiosk(Request $request)
    {
        try {
            // Alias kiosk_user to user_id for backward compatibility
            if ($request->has('kiosk_user') && !$request->has('user_id')) {
                $request->merge(['user_id' => $request->kiosk_user]);
            }

            $validated = $request->validate([
                'kiosk_code' => 'required|string|exists:kiosks,kiosk_code',
                'port_number' => 'nullable|integer|min:1|max:3', // Made optional for machine redemption
                'user_id' => 'required|string',
                'points_to_redeem' => 'required|integer|min:1',
                'timestamp' => 'required|integer',
                'signature' => 'required|string',
            ]);

            // 1. Verify Signature
            $secret = env('KIOSK_SECRET_KEY', 'default_secret_key');
            // Payload: kiosk_code + user_id + points_to_redeem + timestamp
            $payload = $validated['kiosk_code'] . $validated['user_id'] . $validated['points_to_redeem'] . $validated['timestamp'];
            $expectedSignature = hash_hmac('sha256', $payload, $secret);

            if (!hash_equals($expectedSignature, $validated['signature'])) {
                return response()->json(['success' => false, 'message' => 'Invalid signature'], 401);
            }

            // 2. Find Kiosk and Check Status
            $kiosk = \App\Models\Kiosk::where('kiosk_code', $validated['kiosk_code'])->firstOrFail();

            // --- START HANDSHAKE VALIDATION ---
            // 1. Connectivity Check
            if (!$kiosk->last_active || $kiosk->last_active->diffInSeconds(now()) > 30) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kiosk Offline',
                    'error_code' => 'KIOSK_OFFLINE'
                ], 403);
            }

            // 2. Port Status Check
            $portNumber = $validated['port_number'] ?? null;
            if ($portNumber) {
                $ports = $kiosk->details['ports'] ?? [];
                $portStatus = 'unknown';
                foreach ($ports as $port) {
                    if (isset($port['port']) && $port['port'] == $portNumber) {
                        $portStatus = $port['status'] ?? 'unknown';
                        break;
                    }
                }

                if ($portStatus === 'active' || $portStatus === 'busy') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Port Busy',
                        'error_code' => 'PORT_BUSY'
                    ], 400);
                }
            }
            // --- END HANDSHAKE VALIDATION ---

            // 3. Find User
            $userId = $validated['user_id'];
            if (str_starts_with($userId, 'user_')) {
                $userId = str_replace('user_', '', $userId);
            }

            $user = \App\Models\KioskUser::find($userId);
            // Fallback: search by email or username if needed, but ID is safest. 

            if (!$user) {
                return response()->json(['success' => false, 'message' => 'User not found'], 404);
            }

            // 3. Check Balance
            if ($user->points_balance < $validated['points_to_redeem']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient funds',
                    'current_balance' => $user->points_balance
                ], 400);
            }

            // 4. Deduct Points & Transaction
            DB::beginTransaction();
            try {
                $user->points_balance -= $validated['points_to_redeem'];
                $user->save();

                $txn = PointsTransaction::create([
                    'user_id' => $user->id,
                    'transaction_type' => 'redeemed',
                    'type' => 'charge',
                    'title' => 'Kiosk Redemption',
                    'points' => -$validated['points_to_redeem'],
                    'balance_after' => $user->points_balance,
                    'status' => 'completed',
                    'reference_type' => 'kiosk_redemption',
                    'reference_id' => $validated['timestamp'], // Use timestamp or generate UUID
                    'description' => "Redeemed at Kiosk " . $validated['kiosk_code'],
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'new_balance' => $user->points_balance,
                    'transaction_id' => $txn->id, // Or UUID if available
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Kiosk Redeem Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Redemption failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
