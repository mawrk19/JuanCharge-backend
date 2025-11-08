<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\KioskUser;
use Illuminate\Support\Facades\Hash;

class TestKioskUserSeeder extends Seeder
{
    /**
     * Run the database seeds for testing purposes.
     * Creates a kiosk user with known credentials: test@test.com / password
     */
    public function run()
    {
        KioskUser::create([
            'name' => 'Test User',
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@test.com',
            'password' => Hash::make('password'),
            'role' => 'patron',
            'birth_date' => '1990-01-01',
            'phone_number' => '09171234567',
            'points_balance' => 100,
            'points_used' => 50,
            'points_total' => 150,
            'total_recyclables_weight_kg' => 5.5,
            'carbon_offset_kg' => 2.75,
        ]);

        $this->command->info('Test kiosk user created: test@test.com / password');
    }
}
