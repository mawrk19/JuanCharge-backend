<?php

namespace Tests\Feature;

use App\Models\Kiosk;
use App\Models\KioskUser;
use App\Models\PortActivation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortActivationTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_port_activation()
    {
        $user = KioskUser::create([
            'name' => 'Test Patron',
            'first_name' => 'Test',
            'last_name' => 'Patron',
            'email' => 'patron@example.com',
            'password' => bcrypt('password'),
            'points_balance' => 100
        ]);

        $kiosk = Kiosk::create([
            'kiosk_code' => 'K-TEST-001',
            'location' => 'Test Location',
            'status' => 'active'
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/ports/activate', [
                'kiosk_code' => 'K-TEST-001',
                'port_number' => 1,
                'points' => 50
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'new_balance' => 50
                ]
            ]);

        $this->assertDatabaseHas('port_activations', [
            'kiosk_id' => $kiosk->id,
            'user_id' => $user->id,
            'port_number' => 1,
            'points' => 50,
            'status' => 'pending'
        ]);

        $this->assertDatabaseHas('points_transactions', [
            'user_id' => $user->id,
            'points' => 50,
            'transaction_type' => 'redeemed'
        ]);
    }

    public function test_kiosk_heartbeat_activation_delivery()
    {
        $user = KioskUser::create([
            'name' => 'Test Patron',
            'first_name' => 'Test',
            'last_name' => 'Patron',
            'email' => 'patron@example.com',
            'password' => bcrypt('password'),
            'points_balance' => 100
        ]);

        $kiosk = Kiosk::create([
            'kiosk_code' => 'K-TEST-001',
            'location' => 'Test Location',
            'status' => 'active'
        ]);

        // Pre-create activation
        $activation = PortActivation::create([
            'kiosk_id' => $kiosk->id,
            'user_id' => $user->id,
            'port_number' => 2,
            'points' => 20,
            'duration_seconds' => 1200,
            'status' => 'pending'
        ]);

        $response = $this->postJson('/api/kiosk/heartbeat', [
            'kiosk_code' => 'K-TEST-001',
            'status' => 'online'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'pending_activations' => [
                    [
                        'id' => $activation->id,
                        'port' => 2,
                        'points' => 20,
                        'duration' => 1200
                    ]
                ]
            ]);

        $this->assertDatabaseHas('port_activations', [
            'id' => $activation->id,
            'status' => 'sent'
        ]);
    }
}