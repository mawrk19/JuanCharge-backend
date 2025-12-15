<?php

namespace App\Http\Controllers;

use App\Models\ChargingSession;
use App\Models\Kiosk;
use App\Models\KioskUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    /**
     * Get LGU dashboard statistics
     * Returns system-wide aggregated metrics for all users and kiosks
     */
    public function getStats()
    {
        try {
            // Get total recyclables weight from all kiosk users
            $totalRecyclablesKg = KioskUser::sum('total_recyclables_weight') ?? 0;

            // Get total energy dispensed from all charging sessions (completed and cancelled)
            // Energy is stored in Wh, convert to kWh
            $totalEnergyWh = ChargingSession::whereIn('status', ['completed', 'cancelled'])
                ->sum('energy_wh') ?? 0;
            $totalEnergyKwh = $totalEnergyWh / 1000;

            // Calculate CO2 saved (0.5 kg CO2 per kWh)
            $co2SavedKg = $totalEnergyKwh * 0.5;

            // Count online kiosks (status = 'active')
            $onlineKiosksCount = Kiosk::where('status', 'active')->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_recyclables_kg' => round($totalRecyclablesKg, 2),
                    'total_energy_kwh' => round($totalEnergyKwh, 2),
                    'co2_saved_kg' => round($co2SavedKg, 2),
                    'online_kiosks_count' => $onlineKiosksCount
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to get dashboard stats: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve dashboard statistics: ' . $e->getMessage()
            ], 500);
        }
    }
}
