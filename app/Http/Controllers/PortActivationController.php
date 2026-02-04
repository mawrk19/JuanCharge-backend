<?php

namespace App\Http\Controllers;

use App\Models\Kiosk;
use App\Models\PortActivation;
use App\Models\PointsTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PortActivationController extends Controller
{
    /**
     * Activate a specific port on a Kiosk (Seamless Activation)
     */
    public function activate(Request $request)
    {
        $validated = $request->validate([
            'kiosk_id' => 'required_without:kiosk_code|string',
            'kiosk_code' => 'required_without:kiosk_id|string',
            'port' => 'required_without:port_number|integer|min:1|max:3',
            'port_number' => 'required_without:port|integer|min:1|max:3',
            'points' => 'nullable|integer|min:1',
        ]);

        $kioskCode = $validated['kiosk_id'] ?? $validated['kiosk_code'];
        $portNumber = $validated['port'] ?? $validated['port_number'];

        $user = auth()->user();
        $pointsToDeduct = $validated['points'] ?? 50;
        $duration = $pointsToDeduct * 60;

        if ($user->points_balance < $pointsToDeduct) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient points balance. Need ' . $pointsToDeduct . ' points.'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $kiosk = Kiosk::where('kiosk_code', $kioskCode)->firstOrFail();

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

            // 1. Deduct points
            $user->points_balance -= $pointsToDeduct;
            $user->save();

            // 2. Create Points Transaction
            PointsTransaction::create([
                'user_id' => $user->id,
                'transaction_type' => 'redeemed',
                'type' => 'charge',
                'title' => 'Port Activation',
                'points' => $pointsToDeduct,
                'balance_after' => $user->points_balance,
                'status' => 'completed',
                'description' => "Activated port {$portNumber} on Kiosk {$kioskCode}",
            ]);

            // 3. Create Port Activation Command
            $activation = PortActivation::create([
                'kiosk_id' => $kiosk->id,
                'user_id' => $user->id,
                'port_number' => $portNumber,
                'status' => 'pending',
                'points' => $pointsToDeduct,
                'duration_seconds' => $duration,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Activation command queued. Your port will start shortly.',
                'session_id' => $activation->id, // Matching handoff prompt
                'data' => [
                    'session_id' => $activation->id,
                    'activation_id' => $activation->id,
                    'new_balance' => $user->points_balance
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate activation: ' . $e->getMessage()
            ], 500);
        }
    }
}
