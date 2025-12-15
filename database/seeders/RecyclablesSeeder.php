<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\KioskUser;

class RecyclablesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Updates kiosk users with realistic recyclables weight data
     *
     * @return void
     */
    public function run()
    {
        $users = KioskUser::all();

        if ($users->isEmpty()) {
            $this->command->warn('⚠ No kiosk users found. Please run KioskUserSeeder first.');
            return;
        }

        $totalRecyclables = 0;

        foreach ($users as $user) {
            // Generate random recyclables weight between 5kg and 150kg
            $recyclablesWeight = rand(50, 1500) / 10; // Gives 5.0 to 150.0

            $user->total_recyclables_weight = $recyclablesWeight;
            $user->save();

            $totalRecyclables += $recyclablesWeight;
        }

        $this->command->info("✓ Updated {$users->count()} kiosk users with recyclables data");
        $this->command->info("  Total Recyclables: " . round($totalRecyclables, 2) . " kg");
    }
}
