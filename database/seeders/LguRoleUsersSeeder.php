<?php

namespace Database\Seeders;

use App\Models\Lgu;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class LguRoleUsersSeeder extends Seeder
{
    /**
     * Seed 10 LGU users (admin, staff, technician) into unified users table.
     */
    public function run(): void
    {
        $lgu = Lgu::firstOrCreate(
            ['name' => 'Seeded LGU'],
            ['status' => 'active']
        );

        $roleIds = [Role::LGU_ADMIN, Role::LGU_STAFF, Role::LGU_TECHNICIAN];

        for ($i = 1; $i <= 10; $i++) {
            $firstName = 'LGU';
            $lastName = 'User ' . $i;
            $roleId = $roleIds[($i - 1) % count($roleIds)];

            User::updateOrCreate(
                ['email' => 'lguuser' . $i . '@test.com'],
                [
                    'name' => $firstName . ' ' . $lastName,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'password' => Hash::make('password123'),
                    'role_id' => $roleId,
                    'lgu_id' => $lgu->id,
                    'status' => 'active',
                    'is_first_login' => false,
                    'email_verified_at' => now(),
                ]
            );
        }

        $this->command->info('10 LGU users seeded in users table with password: password123');
    }
}
