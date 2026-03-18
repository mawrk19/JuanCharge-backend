<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RecyclingLog;
use App\Models\Kiosk;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class RecyclingLogController extends Controller
{
    /**
     * Sync recycling logs from Kiosk.
     * Endpoint: POST /api/kiosk/recycling-logs/sync
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sync(Request $request)
    {
        // 1. Signature Validation Middleware Logic (HMAC SHA256)
        // Header: X-Kiosk-Signature (or similar)
        $signature = $request->header('X-Signature') ?? $request->header('X-Kiosk-Signature');
        if (is_string($signature) && str_starts_with($signature, 'sha256=')) {
            $signature = substr($signature, 7);
        }

        $payload = $request->getContent();
        // Resolve kiosk secret from config first, then legacy env key for backward compatibility.
        $secret = config('app.kiosk_shared_secret') ?: env('KIOSK_SECRET_KEY') ?: env('KIOSK_SHARED_SECRET');

        // ALLOW LOCAL DEBUGGING: properties validation if simple test or missing config
        if (config('app.debug') && (!$signature || !$secret)) {
            Log::warning('Recycling Sync: Skipping signature check (Debug Mode enabled)');
        } else {
            // Production / Strict Mode
            if (!$signature) {
                return response()->json(['message' => 'Missing Signature'], 401);
            }

            if (!$secret) {
                Log::error('Kiosk sync secret is not configured (KIOSK_SHARED_SECRET/KIOSK_SECRET_KEY)');
                return response()->json(['message' => 'Server Configuration Error'], 500);
            }

            // Calculate expected HMAC
            $expectedSignature = hash_hmac('sha256', $payload, $secret);

            // Verify signature (prevent timing attacks)
            if (!hash_equals($expectedSignature, $signature)) {
                return response()->json(['message' => 'Invalid Signature'], 403);
            }
        }

        // 2. Parse Data
        $data = json_decode($payload, true);

        if (!isset($data['kiosk_code']) || !isset($data['logs']) || !is_array($data['logs'])) {
            return response()->json(['message' => 'Invalid Payload Structure'], 400);
        }

        $kioskCode = $data['kiosk_code'];
        $logs = $data['logs'];

        // Find associated Kiosk (linking logic)
        $kiosk = Kiosk::where('kiosk_code', $kioskCode)->first();
        $kioskId = $kiosk ? $kiosk->id : null;

        $savedCount = 0;

        // 3. Process Logs – simply insert each item; duplicates are allowed
        foreach ($logs as $logItem) {
            try {
                $transactionId = $logItem['transaction_id'] ?? null;
                // Parse date - safely handle timezone or format
                $scannedAt = isset($logItem['scanned_at']) ? Carbon::parse($logItem['scanned_at']) : now();

                // Insert a new row for every item
                $log = new RecyclingLog();
                $log->kiosk_code = $kioskCode;
                $log->kiosk_id = $kioskId;
                $log->transaction_id = $transactionId;
                $log->item_type = $logItem['item_type'] ?? 'unknown';
                $log->points_earned = $logItem['points'] ?? 0;
                $log->hardware_timestamp = $scannedAt;

                // Explicitly handle nullable fields
                $log->weight_kg = null;
                $log->count = 1;
                $log->status = 'completed';

                $log->save();
                $savedCount++;

            } catch (\Exception $e) {
                // Change continue to log error to avoid silent failures in dev
                Log::error("Failed to sync log item for kiosk $kioskCode: " . $e->getMessage());
                // In production, we might want to return partial success or fail explicitly.
                // For now, we continue processing other items.
            }
        }

        return response()->json([
            'message' => 'Sync successful',
            'saved_count' => $savedCount
        ], 200);
    }
}
