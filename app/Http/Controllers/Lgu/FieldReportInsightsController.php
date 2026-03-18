<?php

namespace App\Http\Controllers\Lgu;

use App\Http\Controllers\Controller;
use App\Models\FieldReport;
use App\Models\Kiosk;
use App\Models\MaintenanceTicket;
use App\Models\MissedCollectionAlert;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FieldReportInsightsController extends Controller
{
    public function kpi(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->hasAnyRole([Role::SUPER_ADMIN, Role::LGU_ADMIN, Role::LGU_STAFF, Role::LGU_TECHNICIAN])) {
            return response()->json(['message' => 'unauthorized', 'data' => null], 403);
        }

        $validated = $request->validate([
            'month' => 'required|regex:/^\d{4}-\d{2}$/',
            'lgu_id' => 'nullable|exists:lgus,id',
        ]);

        $monthStart = Carbon::createFromFormat('Y-m', $validated['month'], config('app.timezone'))->startOfMonth();
        $monthEnd = (clone $monthStart)->endOfMonth();

        $lguId = $this->resolveLguId($user, $request);
        if ($lguId === null && !$user->isSuperAdmin()) {
            return response()->json(['message' => 'unauthorized', 'data' => null], 403);
        }

        $kioskQuery = Kiosk::query();
        if ($lguId !== null) {
            $kioskQuery->where('lgu_id', $lguId);
        }

        $kiosks = $kioskQuery->with('collectionSchedule')->get();
        $expectedCollections = 0;

        foreach ($kiosks as $kiosk) {
            $schedule = $kiosk->collectionSchedule;
            if (!$schedule || !$schedule->is_active) {
                continue;
            }

            $days = array_map('strtolower', (array) $schedule->collection_days);
            $cursor = $monthStart->copy();
            while ($cursor->lte($monthEnd)) {
                if (in_array(strtolower($cursor->format('l')), $days, true)) {
                    $expectedCollections++;
                }
                $cursor->addDay();
            }
        }

        $reportsQuery = FieldReport::where('activity_type', 'collection_completed')
            ->where('verification_status', 'verified')
            ->whereBetween('submitted_at', [$monthStart, $monthEnd]);

        if ($lguId !== null) {
            $reportsQuery->whereHas('kiosk', function ($q) use ($lguId) {
                $q->where('lgu_id', $lguId);
            });
        }

        $completedCollections = $reportsQuery->get()
            ->unique(function ($row) {
                return $row->kiosk_id . '|' . Carbon::parse($row->submitted_at)->toDateString();
            })
            ->count();

        $missedCollectionsCount = max($expectedCollections - $completedCollections, 0);
        $serviceReliability = $expectedCollections > 0
            ? round(($completedCollections / $expectedCollections) * 100, 2)
            : 0.0;

        $avgRepairQuery = MaintenanceTicket::whereNotNull('closed_at')
            ->whereBetween('closed_at', [$monthStart, $monthEnd]);

        if ($lguId !== null) {
            $avgRepairQuery->whereHas('kiosk', function ($q) use ($lguId) {
                $q->where('lgu_id', $lguId);
            });
        }

        $averageRepairDays = (float) ($avgRepairQuery
            ->select(DB::raw('AVG(TIMESTAMPDIFF(HOUR, created_at, closed_at))/24 as avg_days'))
            ->value('avg_days') ?? 0);

        $staffQuery = FieldReport::query()
            ->whereBetween('submitted_at', [$monthStart, $monthEnd]);

        if ($lguId !== null) {
            $staffQuery->whereHas('kiosk', function ($q) use ($lguId) {
                $q->where('lgu_id', $lguId);
            });
        }

        $staffRows = $staffQuery
            ->select('submitted_by_user_id')
            ->selectRaw('COUNT(*) as submitted_reports')
            ->selectRaw('SUM(CASE WHEN verification_status = "verified" THEN 1 ELSE 0 END) as verified_reports')
            ->selectRaw('SUM(CASE WHEN verification_status = "rejected" THEN 1 ELSE 0 END) as rejected_reports')
            ->selectRaw('SUM(CASE WHEN activity_type = "collection_completed" THEN 1 ELSE 0 END) as collection_completed_reports')
            ->selectRaw('SUM(CASE WHEN schedule_alignment = "out_of_schedule" THEN 1 ELSE 0 END) as out_of_schedule_reports')
            ->groupBy('submitted_by_user_id')
            ->get();

        $staff = User::whereIn('id', $staffRows->pluck('submitted_by_user_id')->filter()->values())
            ->get(['id', 'name', 'email'])
            ->keyBy('id');

        $staffPerformance = $staffRows->map(function ($row) use ($staff) {
            $actor = $staff->get($row->submitted_by_user_id);

            return [
                'user_id' => (int) $row->submitted_by_user_id,
                'name' => $actor ? $actor->name : null,
                'email' => $actor ? $actor->email : null,
                'submitted_reports' => (int) $row->submitted_reports,
                'verified_reports' => (int) $row->verified_reports,
                'rejected_reports' => (int) $row->rejected_reports,
                'collection_completed_reports' => (int) $row->collection_completed_reports,
                'out_of_schedule_reports' => (int) $row->out_of_schedule_reports,
            ];
        })->values();

        return response()->json([
            'message' => 'success',
            'data' => [
                'month' => $validated['month'],
                'service_reliability_percent' => $serviceReliability,
                'average_repair_days' => round($averageRepairDays, 2),
                'missed_collections_count' => (int) $missedCollectionsCount,
                'staff_performance' => $staffPerformance,
            ],
            'meta' => [
                'expected_collections' => (int) $expectedCollections,
                'completed_collections' => (int) $completedCollections,
            ],
        ]);
    }

    public function missedCollections(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->hasAnyRole([Role::SUPER_ADMIN, Role::LGU_ADMIN, Role::LGU_STAFF, Role::LGU_TECHNICIAN])) {
            return response()->json(['message' => 'unauthorized', 'data' => null], 403);
        }

        $lguId = $this->resolveLguId($user, $request);
        if ($lguId === null && !$user->isSuperAdmin()) {
            return response()->json(['message' => 'unauthorized', 'data' => null], 403);
        }

        $query = MissedCollectionAlert::with([
            'kiosk:id,kiosk_code,location,status,lgu_id,last_serviced_at,collection_schedule_id',
            'kiosk.collectionSchedule:id,name,collection_days,notify_time,is_active',
            'lgu:id,name',
        ])->whereNull('resolved_at');

        if ($lguId !== null) {
            $query->where('lgu_id', $lguId);
        }

        $alerts = $query->orderByDesc('scheduled_date')->get();

        return response()->json([
            'message' => 'success',
            'data' => $alerts,
            'meta' => ['total' => $alerts->count()],
        ]);
    }

    private function resolveLguId($user, Request $request): ?int
    {
        if ($user->isSuperAdmin()) {
            return $request->filled('lgu_id') ? (int) $request->lgu_id : null;
        }

        return $user->isLguRole() ? (int) $user->lgu_id : null;
    }
}
