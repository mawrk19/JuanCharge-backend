<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\LguUser;
use App\Models\Lgu;
use Illuminate\Support\Facades\Hash;

class LguLoginTest extends TestCase
{
    /** @test */
    public function an_lgu_user_can_login_with_correct_credentials()
    {
        // 1. Setup: Create LGU and User
        $lgu = Lgu::create(['name' => 'Test LGU', 'status' => 'active']);
        $user = LguUser::create([
            'first_name' => 'Login',
            'last_name' => 'Test',
            'email' => 'login-test@lgu.com',
            'password' => 'password', // Will be hashed by model boot
            'lgu_id' => $lgu->id,
            'is_first_login' => false
        ]);

        // 2. Action: Login request
        $response = $this->postJson('/api/auth/login', [
            'email' => 'login-test@lgu.com',
            'password' => 'password'
        ]);

        // 3. Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'user_type' => 'lgu_user'
            ]);

        $this->assertNotNull($response->json('token'));
    }

    /** @test */
    public function login_fails_with_incorrect_password()
    {
        $lgu = Lgu::create(['name' => 'Test LGU', 'status' => 'active']);
        $user = LguUser::create([
            'first_name' => 'Login',
            'last_name' => 'Fail',
            'email' => 'login-fail@lgu.com',
            'password' => 'password',
            'lgu_id' => $lgu->id
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'login-fail@lgu.com',
            'password' => 'wrong-password'
        ]);

        $response->assertStatus(401)
            ->assertJson(['success' => false]);
    }
}
