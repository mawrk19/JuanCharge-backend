<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\KioskUser;
use Illuminate\Support\Facades\Hash;

class TestMobileUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates test mobile patron users for testing mobile app authentication
     *
     * @return void
     */
    public function run()
    {
        // Test User 1: Complete profile
        KioskUser::updateOrCreate(
            ['email' => 'mobile@test.com'],
            [
                'name' => 'Mobile Test User',
                'first_name' => 'Mobile',
                'last_name' => 'Tester',
                'email' => 'mobile@test.com',
                'password' => Hash::make('password'),
                'contact_number' => '09123456789',
                'points_balance' => 1500,
                'points_total' => 3000,
                'points_used' => 1500,
                'leaderboard_rank' => 1,
                'total_recyclables_weight' => 25.5,
                'total_charging_time' => 180,
            ]
        );

        // Test User 2: Incomplete profile (for testing profile update prompts)
        KioskUser::updateOrCreate(
            ['email' => 'patron@test.com'],
            [
                'name' => 'Patron User',
                // Use empty strings for nullable text fields to avoid strict DB constraints
                'first_name' => '',
                'last_name' => '',
                'email' => 'patron@test.com',
                'password' => Hash::make('password'),
                'contact_number' => '',
                'points_balance' => 500,
                'points_total' => 500,
                'points_used' => 0,
                'leaderboard_rank' => null,
                'total_recyclables_weight' => 0,
                'total_charging_time' => 0,
            ]
        );

        // Test User 3: High points user
        KioskUser::updateOrCreate(
            ['email' => 'vip@test.com'],
            [
                'name' => 'VIP Patron',
                'first_name' => 'VIP',
                'last_name' => 'Patron',
                'email' => 'vip@test.com',
                'password' => Hash::make('password'),
                'contact_number' => '09987654321',
                'points_balance' => 10000,
                'points_total' => 15000,
                'points_used' => 5000,
                'leaderboard_rank' => 1,
                'total_recyclables_weight' => 150.0,
                'total_charging_time' => 1200,
            ]
        );

        $this->command->info('✓ Created 3 test mobile patron users');
        $this->command->info('  1. mobile@test.com / password (complete profile)');
        $this->command->info('  2. patron@test.com / password (incomplete profile)');
        $this->command->info('  3. vip@test.com / password (high points user)');
    }
}
