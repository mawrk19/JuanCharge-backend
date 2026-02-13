<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LguUser;
use App\Models\Lgu;
use Illuminate\Support\Facades\Hash;

class QuickLguUserSeeder extends Seeder
{
    public function run()
    {
        // Ensure at least one LGU exists
        $lgu = Lgu::firstOrCreate(
            ['name' => 'Seeded Test LGU'],
            ['status' => 'active']
        );

        $users = [
            [
                'first_name' => 'LGU',
                'last_name' => 'Admin One',
                'email' => 'lguadmin1@test.com',
                'password' => 'password',
                'lgu_id' => $lgu->id,
            ],
            [
                'first_name' => 'LGU',
                'last_name' => 'Admin Two',
                'email' => 'lguadmin2@test.com',
                'password' => 'password',
                'lgu_id' => $lgu->id,
            ]
        ];

        foreach ($users as $userData) {
            LguUser::updateOrCreate(
                ['email' => $userData['email']],
                array_merge($userData, ['is_first_login' => false])
            );
        }

        $this->command->info('2 LGU users seeded successfully with password: password');
    }
}
