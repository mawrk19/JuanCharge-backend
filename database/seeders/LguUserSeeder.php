<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LguUser;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class LguUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $roles = ['Admin', 'Manager', 'Staff', 'Supervisor', 'Analyst'];
        $firstNames = [
            'Juan',
            'Maria',
            'Jose',
            'Ana',
            'Pedro',
            'Carmen',
            'Miguel',
            'Rosa',
            'Antonio',
            'Elena',
            'Carlos',
            'Sofia',
            'Manuel',
            'Isabel',
            'Rafael',
            'Teresa',
            'Diego',
            'Patricia',
            'Luis',
            'Monica'
        ];
        $lastNames = [
            'Cruz',
            'Santos',
            'Reyes',
            'Garcia',
            'Fernandez',
            'Lopez',
            'Martinez',
            'Rodriguez',
            'Gonzalez',
            'Perez',
            'Sanchez',
            'Ramirez',
            'Torres',
            'Flores',
            'Rivera',
            'Gomez',
            'Diaz',
            'Morales',
            'Mendoza',
            'Castillo'
        ];

        for ($i = 1; $i <= 20; $i++) {
            $firstName = $firstNames[$i - 1];
            $lastName = $lastNames[$i - 1];
            $name = $firstName . ' ' . $lastName;

            LguUser::create([
                'name' => $name,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => strtolower($firstName) . '.' . strtolower($lastName) . '@lgu.gov.ph',
                'is_first_login' => true,
            ]);
        }

        $this->command->info('20 LGU users created successfully!');
    }
}
