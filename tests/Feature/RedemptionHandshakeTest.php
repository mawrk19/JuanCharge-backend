<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Kiosk;
use App\Models\KioskUser;
use App\Models\ChargingSession;
use Carbon\Carbon;

class RedemptionHandshakeTest extends TestCase
{
    protected $user;
    protected $kiosk;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure we have a test user
        $this->user = KioskUser::firstOrCreate(
            ['email' => 'handshake_tester@example.com'],
            [
                'name' => 'Handshake Tester',
                'first_name' => 'Handshake',
                'last_name' => 'Tester',
                'password' => bcrypt('password'),
                'points_balance' => 500,
                'points_total' => 500
            ]
        );

        // CLEANUP: Remove any active sessions for this user to avoid 400 "Already have active session"
        ChargingSession::where('user_id', $this->user->id)->delete();

        // Ensure we have a test kiosk with fresh last_active
        $this->kiosk = Kiosk::updateOrCreate(
            ['kiosk_code' => 'HANDSHAKE-001'],
            [
                'location' => 'Testing Center',
                'status' => 'active',
                'last_active' => now(),
                'details' => [
                    'ports' => [
                        ['port' => 1, 'status' => 'idle'],
                        ['port' => 2, 'status' => 'idle'],
                        ['port' => 3, 'status' => 'idle']
                    ]
                ]
            ]
        );
    }

    public function test_kiosk_status_endpoint()
    {
        $response = $this->getJson("/api/kiosk/status/{$this->kiosk->kiosk_code}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'kiosk_code' => $this->kiosk->kiosk_code,
                    'is_online' => true
                ]
            ]);
    }

    public function test_redemption_fails_when_kiosk_is_offline()
    {
        // Make kiosk offline (last_active > 30s)
        $this->kiosk->update(['last_active' => now()->subSeconds(45)]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/charging/redeem', [
                'kiosk_code' => $this->kiosk->kiosk_code,
                'points' => 50,
                'port_number' => 1
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Kiosk Offline',
                'error_code' => 'KIOSK_OFFLINE'
            ]);
    }

    public function test_redemption_fails_when_port_is_busy()
    {
        // Kiosk is online
        $this->kiosk->update([
            'last_active' => now(),
            'details' => [
                'ports' => [
                    ['port' => 1, 'status' => 'active'], // Busy
                    ['port' => 2, 'status' => 'idle']
                ]
            ]
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/charging/redeem', [
                'kiosk_code' => $this->kiosk->kiosk_code,
                'points' => 50,
                'port_number' => 1
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Port Busy',
                'error_code' => 'PORT_BUSY'
            ]);
    }

    public function test_redemption_succeeds_when_online_and_idle()
    {
        // Reset kiosk to good state
        $this->kiosk->update([
            'last_active' => now(),
            'details' => [
                'ports' => [
                    ['port' => 1, 'status' => 'idle']
                ]
            ]
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/charging/redeem', [
                'kiosk_code' => $this->kiosk->kiosk_code,
                'points' => 50,
                'port_number' => 1
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('charging_sessions', [
            'kiosk_id' => $this->kiosk->id,
            'port_number' => 1,
            'status' => 'active'
        ]);
    }

    public function test_kiosk_redemption_with_legacy_payload()
    {
        // Legacy payload has kiosk_user and NO port_number
        // We'll mock the signature as well
        $secret = env('KIOSK_SECRET_KEY', 'default_secret_key');
        $timestamp = time();
        $payload = $this->kiosk->kiosk_code . $this->user->id . 50 . $timestamp;
        $signature = hash_hmac('sha256', $payload, $secret);

        $response = $this->postJson('/api/kiosk/redeem', [
            'kiosk_code' => $this->kiosk->kiosk_code,
            'kiosk_user' => (string) $this->user->id,
            'points_to_redeem' => 50,
            'timestamp' => $timestamp,
            'signature' => $signature
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('points_transactions', [
            'user_id' => $this->user->id,
            'transaction_type' => 'redeemed',
            'points' => -50
        ]);
    }

    public function test_port_activation_enforces_handshake()
    {
        // Test Port Activation Controller also has checks
        $this->kiosk->update(['last_active' => now()->subSeconds(60)]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/ports/activate', [
                'kiosk_code' => $this->kiosk->kiosk_code,
                'port' => 1,
                'points' => 50
            ]);

        $response->assertStatus(403)
            ->assertJson(['message' => 'Kiosk Offline']);
    }
}
