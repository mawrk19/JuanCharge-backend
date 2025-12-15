<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DashboardTestSeeder extends Seeder
{
    /**
     * Seed the database with test data for dashboard statistics.
     * Run this seeder to populate the database with realistic data
     * to test the /api/dashboard/stats endpoint
     *
     * @return void
     */
    public function run()
    {
        $this->command->info('');
        $this->command->info('=== Seeding Dashboard Test Data ===');
        $this->command->info('');

        // 1. Create kiosks first
        $this->command->info('1. Creating kiosks...');
        $this->call(MultipleKiosksSeeder::class);
        $this->command->info('');

        // 2. Create kiosk users
        $this->command->info('2. Creating kiosk users...');
        $this->call(TestMobileUserSeeder::class);
        $this->command->info('');

        // 3. Add recyclables data to users
        $this->command->info('3. Adding recyclables data...');
        $this->call(RecyclablesSeeder::class);
        $this->command->info('');

        // 4. Create charging sessions
        $this->command->info('4. Creating charging sessions...');
        $this->call(ChargingSessionSeeder::class);
        $this->command->info('');

        $this->command->info('=== Dashboard Test Data Seeding Complete! ===');
        $this->command->info('');
        $this->command->info('You can now test the dashboard endpoint:');
        $this->command->info('  powershell -ExecutionPolicy Bypass -File test-dashboard-stats.ps1');
        $this->command->info('');
    }
}
