<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class TestAdminSeeder extends Seeder
{
    public function run()
    {
        User::updateOrCreate(
            ['email' => 'admin@example.com'], // avoid duplicates
            [
                'name' => 'Test Admin',
                'password' => Hash::make('password123'), // bcrypt hash
                'email_verified_at' => now(),
            ]
        );
    }
}
