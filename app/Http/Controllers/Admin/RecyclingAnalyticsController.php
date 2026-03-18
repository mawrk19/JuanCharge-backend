<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RecyclingLog;
use Illuminate\Support\Facades\Schema;
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
            // Check if source table exists
            if (!Schema::hasTable('recycling_logs')) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'total_items' => 0,
                        'breakdown' => [],
                        'trends' => []
                    ]
                ]);
            }

            // 1. Breakdown by item_type (raw)
            $rawBreakdown = RecyclingLog::select('item_type', DB::raw('SUM(count) as total_count'))
                ->groupBy('item_type')
                ->get();

            // 2b. Normalize labels so UI distribution doesn't lose counts due to inconsistent item_type values.
            $breakdown = $rawBreakdown
                ->groupBy(function ($row) {
                    return $this->normalizeItemType((string) $row->item_type);
                })
                ->map(function ($rows, $itemType) {
                    return [
                        'item_type' => $itemType,
                        'total_count' => (int) $rows->sum(function ($row) {
                            return (int) $row->total_count;
                        }),
                    ];
                })
                ->values();

            $breakdownTotalItems = (int) $breakdown->sum(function ($row) {
                return (int) ($row['total_count'] ?? 0);
            });

            // 2. Total items excluding mixed and glass categories.
            $totalItems = (int) $breakdown
                ->filter(function ($row) {
                    return !in_array($row['item_type'] ?? '', ['mixed', 'glass'], true);
                })
                ->sum(function ($row) {
                    return (int) ($row['total_count'] ?? 0);
                });

            // 3. Daily trends (last 30 days)
            $trends = RecyclingLog::select(
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
                    'breakdown_total_items' => $breakdownTotalItems,
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

    private function normalizeItemType(string $itemType): string
    {
        $normalized = strtolower(trim($itemType));

        if (in_array($normalized, ['plastic', 'pet', 'pet/plastic battles', 'pet/plastic bottles', 'plastic_bottle', 'test bottle'], true)) {
            return 'plastic';
        }

        if (in_array($normalized, ['metal', 'can', 'tin/cans'], true)) {
            return 'metal';
        }

        if (in_array($normalized, ['glass', 'glass_bottle'], true)) {
            return 'glass';
        }

        if ($normalized === 'mixed') {
            return 'mixed';
        }

        // Fold unknown labels into mixed to avoid dropping counts in UIs
        // that only visualize a fixed set of categories.
        return 'mixed';
    }
}
