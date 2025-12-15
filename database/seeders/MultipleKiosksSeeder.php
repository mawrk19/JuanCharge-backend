<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Kiosk;

class MultipleKiosksSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates multiple kiosks with different statuses
     *
     * @return void
     */
    public function run()
    {
        $kiosks = [
            [
                'kiosk_code' => 'KSK-001',
                'location' => 'Downtown Plaza',
                'status' => 'active',
                'serial_number' => 'SN001-2024',
                'mac_address' => '00:1A:2B:3C:4D:5E',
                'ip_address' => '192.168.1.10',
                'software_version' => 'v1.2.0',
                'notes' => 'Main downtown location',
            ],
            [
                'kiosk_code' => 'KSK-002',
                'location' => 'City Hall',
                'status' => 'active',
                'serial_number' => 'SN002-2024',
                'mac_address' => '00:1A:2B:3C:4D:5F',
                'ip_address' => '192.168.1.11',
                'software_version' => 'v1.2.0',
                'notes' => 'Government center location',
            ],
            [
                'kiosk_code' => 'KSK-003',
                'location' => 'University Campus',
                'status' => 'active',
                'serial_number' => 'SN003-2024',
                'mac_address' => '00:1A:2B:3C:4D:60',
                'ip_address' => '192.168.1.12',
                'software_version' => 'v1.2.0',
                'notes' => 'Student center location',
            ],
            [
                'kiosk_code' => 'KSK-004',
                'location' => 'Shopping Mall',
                'status' => 'active',
                'serial_number' => 'SN004-2024',
                'mac_address' => '00:1A:2B:3C:4D:61',
                'ip_address' => '192.168.1.13',
                'software_version' => 'v1.2.0',
                'notes' => 'High traffic retail area',
            ],
            [
                'kiosk_code' => 'KSK-005',
                'location' => 'Public Park',
                'status' => 'active',
                'serial_number' => 'SN005-2024',
                'mac_address' => '00:1A:2B:3C:4D:62',
                'ip_address' => '192.168.1.14',
                'software_version' => 'v1.2.0',
                'notes' => 'Recreational area',
            ],
            [
                'kiosk_code' => 'KSK-006',
                'location' => 'Train Station',
                'status' => 'inactive',
                'serial_number' => 'SN006-2024',
                'mac_address' => '00:1A:2B:3C:4D:63',
                'ip_address' => '192.168.1.15',
                'software_version' => 'v1.1.5',
                'notes' => 'Temporarily offline for updates',
            ],
            [
                'kiosk_code' => 'KSK-007',
                'location' => 'Community Center',
                'status' => 'maintenance',
                'serial_number' => 'SN007-2024',
                'mac_address' => '00:1A:2B:3C:4D:64',
                'ip_address' => '192.168.1.16',
                'software_version' => 'v1.2.0',
                'notes' => 'Under maintenance - hardware repair',
            ],
        ];

        foreach ($kiosks as $kioskData) {
            Kiosk::updateOrCreate(
                ['kiosk_code' => $kioskData['kiosk_code']],
                array_merge($kioskData, [
                    'registered_at' => now()->subDays(rand(30, 180)),
                    'last_active' => $kioskData['status'] === 'active' ? now()->subMinutes(rand(1, 60)) : now()->subDays(rand(1, 7)),
                ])
            );
        }

        $activeCount = Kiosk::where('status', 'active')->count();
        $totalCount = Kiosk::count();

        $this->command->info("✓ Created/Updated {$totalCount} kiosks");
        $this->command->info("  Active: {$activeCount}");
        $this->command->info("  Inactive: " . Kiosk::where('status', 'inactive')->count());
        $this->command->info("  Maintenance: " . Kiosk::where('status', 'maintenance')->count());
    }
}
