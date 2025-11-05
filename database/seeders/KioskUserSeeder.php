<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\KioskUser;
use Illuminate\Support\Facades\Hash;

class KioskUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $firstNames = [
            'Mark', 'Jane', 'John', 'Liza', 'Ryan',
            'Emma', 'David', 'Sarah', 'James', 'Sophia',
            'Robert', 'Olivia', 'Daniel', 'Mia', 'Michael',
            'Isabella', 'William', 'Charlotte', 'Joseph', 'Amelia'
        ];
        $lastNames = [
            'Dela Cruz', 'Bautista', 'Villanueva', 'Aquino', 'Soriano',
            'Castro', 'Mercado', 'Salazar', 'Navarro', 'Aguilar',
            'Ramos', 'Santiago', 'Pascual', 'Valencia', 'Domingo',
            'Tan', 'Lim', 'Ong', 'Sy', 'Chua'
        ];

        for ($i = 1; $i <= 20; $i++) {
            $firstName = $firstNames[$i - 1];
            $lastName = $lastNames[$i - 1];
            $name = $firstName . ' ' . $lastName;
            
            // Generate random points and stats
            $pointsBalance = rand(0, 500);
            $pointsUsed = rand(0, 300);
            $pointsTotal = $pointsBalance + $pointsUsed;
            
            // Generate password: patron + 10 random hex characters
            $randomHex = bin2hex(random_bytes(5));
            $defaultPassword = 'patron' . $randomHex;
            
            KioskUser::create([
                'name' => $name,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => strtolower(str_replace(' ', '', $firstName)) . '.' . strtolower(str_replace(' ', '', $lastName)) . '@email.com',
                'password' => Hash::make($defaultPassword),
                'contact_number' => '09' . rand(100000000, 999999999),
                'points_balance' => $pointsBalance,
                'points_total' => $pointsTotal,
                'points_used' => $pointsUsed,
                'leaderboard_rank' => null,
                'total_recyclables_weight' => round(rand(1000, 50000) / 100, 2), // Random kg
                'total_charging_time' => rand(60, 1000), // Random minutes
            ]);
        }

        $this->command->info('20 Kiosk users created successfully!');
    }
}
