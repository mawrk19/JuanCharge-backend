<?php

namespace Tests\Feature;

use App\Models\Kiosk;
use App\Models\KioskRecyclingLog;
use App\Models\LguUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RecyclingAnalyticsTest extends TestCase
{
    // use RefreshDatabase;

    protected $kiosk;

    protected function setUp(): void
    {
        parent::setUp();
        // Create a kiosk for testing
        $this->kiosk = Kiosk::firstOrCreate(
            ['kiosk_code' => 'UCC-Kiosk-0001'],
            ['location' => 'Test Location', 'status' => 'active']
        );
    }

    public function test_heartbeat_stores_recycling_stats()
    {
        $response = $this->postJson('/api/kiosk/heartbeat', [
            'kiosk_code' => 'UCC-Kiosk-0001',
            'status' => 'online',
            'recycling_stats' => [
                'pet' => 10,
                'can' => 5,
                'glass_bottle' => 2
            ],
            'timestamp' => now()->timestamp * 1000
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('kiosk_recycling_logs', [
            'kiosk_id' => $this->kiosk->id,
            'item_type' => 'pet',
            'count' => 10
        ]);

        $this->assertDatabaseHas('kiosk_recycling_logs', [
            'kiosk_id' => $this->kiosk->id,
            'item_type' => 'can',
            'count' => 5
        ]);

        $this->assertDatabaseHas('kiosk_recycling_logs', [
            'kiosk_id' => $this->kiosk->id,
            'item_type' => 'glass_bottle',
            'count' => 2
        ]);
    }

    public function test_analytics_endpoint_aggregates_data()
    {
        KioskRecyclingLog::create([
            'kiosk_id' => $this->kiosk->id,
            'item_type' => 'pet',
            'count' => 100,
            'created_at' => now()->subDay()
        ]);

        KioskRecyclingLog::create([
            'kiosk_id' => $this->kiosk->id,
            'item_type' => 'can',
            'count' => 50,
            'created_at' => now()
        ]);

        \App\Models\Lgu::firstOrCreate(['id' => 1], ['name' => 'Test LGU']);

        // Mock auth
        $user = LguUser::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'first_name' => 'Admin',
                'last_name' => 'User',
                'password' => 'password',
                'role' => 'admin',
                'birth_date' => '1990-01-01',
                'phone_number' => '09123456789',
                'lgu_id' => 1
            ]
        );

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/admin/analytics/recycling');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true
            ])
            ->assertJsonStructure([
                'data' => [
                    'total_items',
                    'breakdown',
                    'trends'
                ]
            ]);

        $this->assertGreaterThanOrEqual(150, $response->json('data.total_items'));
    }
}
