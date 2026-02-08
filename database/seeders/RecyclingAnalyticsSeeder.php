<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Kiosk;
use App\Models\KioskUser;
use App\Models\RecyclingLog;
use App\Models\KioskRecyclingLog;
use App\Models\Lgu;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class RecyclingAnalyticsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 1. Ensure at least one LGU, Kiosk, and KioskUser exist
        $lgu = Lgu::first() ?: Lgu::create(['name' => 'General Santos City', 'status' => 'active']);

        $kiosk = Kiosk::first();
        if (!$kiosk) {
            $kiosk = new Kiosk();
            $kiosk->lgu_id = $lgu->id;
            $kiosk->kiosk_code = 'KSC-001';
            $kiosk->location = 'City Square';
            $kiosk->status = 'active'; // Changed from 'online' to 'active'
            $kiosk->serial_number = 'SN-DEBUG-001';
            $kiosk->save();
        }

        $user = KioskUser::first() ?: KioskUser::create([
            'first_name' => 'Demo',
            'last_name' => 'Patron',
            'name' => 'Demo Patron',
            'email' => 'patron@example.com',
            'password' => Hash::make('password123'),
            'contact_number' => '09170001111',
            'points_balance' => 100,
            'points_total' => 100
        ]);

        // 2. Clear existing logs to start fresh
        RecyclingLog::truncate();

        // 3. Define Weight constants (in grams)
        $weights = [
            'plastic' => 18.5, // Coke Mismo 290ml
            'metal' => 14.9,   // Aluminum Can 355ml
        ];

        // 4. Generate specific total counts over the last 30 days
        $now = Carbon::now();

        $types = [
            'plastic' => 68,
            'metal' => 20
        ];

        foreach ($types as $type => $totalTarget) {
            for ($k = 0; $k < $totalTarget; $k++) {
                $count = 1; // Each entry is 1 piece to match the requested total exactly

                // Calculate weight in KG
                $weightKg = ($count * $weights[$type]) / 1000;
                $points = $count * ($type === 'plastic' ? 2 : 5);

                // Pick a random day in the last 30 days
                $logDate = (clone $now)->subDays(rand(0, 29))->subHours(rand(1, 12))->subMinutes(rand(1, 59));

                RecyclingLog::create([
                    'user_id' => $user->id,
                    'kiosk_id' => $kiosk->id,
                    'weight_kg' => $weightKg,
                    'points_earned' => $points,
                    'item_type' => $type,
                    'count' => $count,
                    'hardware_timestamp' => $logDate,
                    'status' => 'completed',
                    'created_at' => $logDate,
                    'updated_at' => $logDate,
                ]);
            }
        }

        $this->command->info('Recycling Analytics seeded successfully!');
        $this->command->info("Weights used: Plastic (PET) = {$weights['plastic']}g, Metal (Can) = {$weights['metal']}g");
    }
}
