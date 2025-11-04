<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Kiosk;

class KioskSeeder extends Seeder
{
    public function run(): void
    {
        Kiosk::create([
            'kiosk_code' => 'KSK-001',
            'location' => 'Downtown',
            'status' => 'active',
            'serial_number' => 'SN123456',
            'mac_address' => '00:1A:2B:3C:4D:5E',
            'ip_address' => '192.168.1.10',
            'software_version' => 'v1.2.0',
            'notes' => 'First deployment kiosk',
            'registered_at' => now(),
            'last_active' => now(),
        ]);
    }
}

