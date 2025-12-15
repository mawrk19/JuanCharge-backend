<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ChargingSession;
use App\Models\KioskUser;
use App\Models\Kiosk;
use Carbon\Carbon;

class ChargingSessionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates completed and cancelled charging sessions with realistic data
     *
     * @return void
     */
    public function run()
    {
        // Get all kiosk users
        $users = KioskUser::all();

        if ($users->isEmpty()) {
            $this->command->warn('⚠ No kiosk users found. Please run KioskUserSeeder first.');
            return;
        }

        // Get all kiosks
        $kiosks = Kiosk::all();

        if ($kiosks->isEmpty()) {
            $this->command->warn('⚠ No kiosks found. Please run KioskSeeder first.');
            return;
        }

        $sessionCount = 0;
        $totalEnergyWh = 0;

        // Create 50 completed charging sessions
        foreach ($users as $user) {
            $sessionsForUser = rand(3, 10);

            for ($i = 0; $i < $sessionsForUser; $i++) {
                $pointsRedeemed = rand(10, 500);
                $durationMinutes = $pointsRedeemed * 1; // 1 point = 1 minute
                $energyWh = $durationMinutes * 0.167; // 10W port: 0.167 Wh/min

                $startTime = Carbon::now()->subDays(rand(1, 30))->subHours(rand(0, 23));
                $endTime = $startTime->copy()->addMinutes($durationMinutes);
                $completedAt = $endTime->copy()->addMinutes(rand(0, 5));

                ChargingSession::create([
                    'session_id' => 'SESSION_' . time() . '_' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8)),
                    'user_id' => $user->id,
                    'kiosk_id' => $kiosks->random()->id,
                    'points_redeemed' => $pointsRedeemed,
                    'energy_wh' => $energyWh,
                    'duration_minutes' => $durationMinutes,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'status' => 'completed',
                    'completed_at' => $completedAt,
                    'created_at' => $startTime,
                    'updated_at' => $completedAt,
                ]);

                $sessionCount++;
                $totalEnergyWh += $energyWh;

                // Small delay to ensure unique session IDs
                usleep(1000);
            }
        }

        // Create some cancelled sessions (about 10% of total)
        $cancelledCount = 0;
        foreach ($users->take(3) as $user) {
            $pointsRedeemed = rand(50, 200);
            $durationMinutes = $pointsRedeemed * 1;
            $energyWh = $durationMinutes * 0.167;

            $startTime = Carbon::now()->subDays(rand(1, 15))->subHours(rand(0, 23));
            $cancelledAt = $startTime->copy()->addMinutes(rand(5, $durationMinutes - 5));
            $endTime = $startTime->copy()->addMinutes($durationMinutes);

            ChargingSession::create([
                'session_id' => 'SESSION_' . time() . '_' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8)),
                'user_id' => $user->id,
                'kiosk_id' => $kiosks->random()->id,
                'points_redeemed' => $pointsRedeemed,
                'energy_wh' => $energyWh,
                'duration_minutes' => $durationMinutes,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'status' => 'cancelled',
                'cancelled_at' => $cancelledAt,
                'created_at' => $startTime,
                'updated_at' => $cancelledAt,
            ]);

            $cancelledCount++;
            $totalEnergyWh += $energyWh;

            usleep(1000);
        }

        $totalEnergyKwh = round($totalEnergyWh / 1000, 2);
        $co2Saved = round($totalEnergyKwh * 0.5, 2);

        $this->command->info("✓ Created {$sessionCount} completed charging sessions");
        $this->command->info("✓ Created {$cancelledCount} cancelled charging sessions");
        $this->command->info("  Total Energy: {$totalEnergyKwh} kWh");
        $this->command->info("  CO2 Saved: {$co2Saved} kg");
    }
}
