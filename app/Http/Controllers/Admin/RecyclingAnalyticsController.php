<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KioskRecyclingLog;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RecyclingAnalyticsController extends Controller
{
    /**
     * Get aggregated recycling analytics.
     */
    public function index()
    {
        try {
            // Check if table exists
            if (!\Illuminate\Support\Facades\Schema::hasTable('kiosk_recycling_logs')) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'total_items' => 0,
                        'breakdown' => [],
                        'trends' => []
                    ]
                ]);
            }

            // 1. Total items recycled (all-time)
            $totalItems = KioskRecyclingLog::sum('count');

            // 2. Breakdown by item_type
            $breakdown = KioskRecyclingLog::select('item_type', DB::raw('SUM(count) as total_count'))
                ->groupBy('item_type')
                ->get();

            // 3. Daily trends (last 30 days)
            $trends = KioskRecyclingLog::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(count) as total_count')
            )
                ->where('created_at', '>=', Carbon::now()->subDays(30))
                ->groupBy('date')
                ->orderBy('date', 'ASC')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_items' => (int) $totalItems,
                    'breakdown' => $breakdown,
                    'trends' => $trends
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch recycling analytics: ' . $e->getMessage()
            ], 500);
        }
    }
}
