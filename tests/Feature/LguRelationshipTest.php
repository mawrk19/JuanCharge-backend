<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class LguRelationshipTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_lgu_can_have_multiple_users_and_kiosks()
    {
        $lgu = \App\Models\Lgu::create([
            'name' => 'Manila City',
            'status' => 'active'
        ]);

        $user1 = \App\Models\LguUser::create([
            'lgu_id' => $lgu->id,
            'name' => 'User One',
            'email' => 'user1@manila.gov',
            'password' => 'secret',
            'first_name' => 'User',
            'last_name' => 'One',
            'role' => 'admin',
            'birth_date' => '1990-01-01',
            'phone_number' => '09123456789'
        ]);

        $kiosk1 = \App\Models\Kiosk::create([
            'lgu_id' => $lgu->id,
            'kiosk_code' => 'MNL-001',
            'location' => 'City Hall'
        ]);

        $this->assertEquals(1, $lgu->users()->count());
        $this->assertEquals(1, $lgu->kiosks()->count());
        $this->assertEquals($lgu->id, $user1->lgu->id);
        $this->assertEquals($lgu->id, $kiosk1->lgu->id);
    }
}
