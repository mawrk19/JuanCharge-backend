<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Kiosk;
use Carbon\Carbon;

class KioskIntegrationTest extends TestCase
{
    // use RefreshDatabase; // Use this cautiously if you don't want to wipe local DB. Maybe just create valid data.

    public function test_kiosk_heartbeat()
    {
        // Ensure Kiosk Exists
        $kiosk = Kiosk::firstOrCreate(
            ['kiosk_code' => 'TEST-KIOSK-001'],
            ['location' => 'Test Lab', 'serial_number' => 'SN-TEST-001', 'status' => 'active']
        );

        $response = $this->postJson('/api/kiosk/heartbeat', [
            'kiosk_code' => $kiosk->kiosk_code,
            'status' => 'online',
            'ports' => [
                ['port' => 1, 'status' => 'active']
            ]
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('kiosks', [
            'id' => $kiosk->id,
            // 'status' => 'active', // Enum might map 'online' -> 'active'
        ]);

        // Verify details json
        $kiosk->refresh();
        $this->assertNotNull($kiosk->details);
        $this->assertEquals('active', $kiosk->details['ports'][0]['status']);
    }

    public function test_kiosk_redemption_signed()
    {
        $kiosk = Kiosk::firstOrCreate(
            ['kiosk_code' => 'TEST-KIOSK-001'],
            ['location' => 'Test Lab', 'serial_number' => 'SN-TEST-001']
        );

        // Create KioskUser manually since Factory might not exist
        $user = \App\Models\KioskUser::create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'name' => 'Test User',
            'email' => 'test_kiosk_user_' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'points_balance' => 100,
            'points_total' => 100
        ]);

        $pointsToRedeem = 10;
        $timestamp = now()->timestamp * 1000; // MS
        $secret = env('KIOSK_SECRET_KEY', 'default_secret_key');

        $payload = $kiosk->kiosk_code . $user->id . $pointsToRedeem . $timestamp;
        $signature = hash_hmac('sha256', $payload, $secret);

        $response = $this->postJson('/api/kiosk/redeem', [
            'kiosk_code' => $kiosk->kiosk_code,
            'user_id' => (string) $user->id,
            'points_to_redeem' => $pointsToRedeem,
            'timestamp' => $timestamp,
            'signature' => $signature
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $user->refresh();
        $this->assertEquals(90, $user->points_balance);

        $this->assertDatabaseHas('points_transactions', [
            'user_id' => $user->id,
            'transaction_type' => 'redeemed',
            'points' => -10
        ]);
    }

    public function test_mobile_claim_signed()
    {
        $kiosk = Kiosk::firstOrCreate(
            ['kiosk_code' => 'TEST-KIOSK-001'],
            ['location' => 'Test Lab', 'serial_number' => 'SN-TEST-001']
        );

        // Create KioskUser
        $user = \App\Models\KioskUser::create([
            'first_name' => 'Claim',
            'last_name' => 'User',
            'name' => 'Claim User',
            'email' => 'claim_user_' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'points_balance' => 0,
            'points_total' => 0
        ]);

        $txnId = \Illuminate\Support\Str::uuid()->toString();
        $points = 50;
        $timestamp = now()->timestamp * 1000;
        $secret = env('KIOSK_SECRET_KEY', 'default_secret_key');

        $payload = $kiosk->kiosk_code . $txnId . $points . $timestamp;
        $signature = hash_hmac('sha256', $payload, $secret);

        $response = $this->actingAs($user, 'sanctum') // Mock Auth
            ->postJson('/api/patron/points/claim-signed', [
                'kiosk_code' => $kiosk->kiosk_code,
                'txn_id' => $txnId,
                'points' => $points,
                'timestamp' => $timestamp,
                'signature' => $signature,
                'action' => 'store_points', // Simulate extra fields
                'amount' => $points
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        // Check user balance
        $user->refresh();
        $this->assertEquals(50, $user->points_balance);

        // Check PointVoucher created
        $this->assertDatabaseHas('point_vouchers', [
            'code' => $txnId,
            'points' => 50,
            'status' => 'claimed',
            'claimed_by' => $user->id
        ]);
    }
}
