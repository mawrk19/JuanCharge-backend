<?php

namespace Tests\Feature;

use App\Models\Lgu;
use App\Models\LguUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LguUserCreationTest extends TestCase
{
    use RefreshDatabase;

    protected $lgu;
    protected $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->lgu = Lgu::create([
            'name' => 'Test LGU',
            'status' => 'active'
        ]);

        $this->adminUser = LguUser::create([
            'lgu_id' => $this->lgu->id,
            'name' => 'Admin User',
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@testlgu.com',
            'password' => 'password',
            'is_first_login' => false
        ]);
    }

    /** @test */
    public function it_can_create_lgu_user_with_explicit_lgu_id()
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->postJson('/api/lgu-users', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@doe.com',
            'lgu_id' => $this->lgu->id
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.lgu_id', $this->lgu->id);

        $this->assertDatabaseHas('lgu_users', [
            'email' => 'john@doe.com',
            'lgu_id' => $this->lgu->id
        ]);
    }

    /** @test */
    public function it_auto_populates_lgu_id_from_authenticated_user()
    {
        Sanctum::actingAs($this->adminUser);

        // Note: lgu_id is omitted here
        $response = $this->postJson('/api/lgu-users', [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane@smith.com'
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.lgu_id', $this->lgu->id);

        $this->assertDatabaseHas('lgu_users', [
            'email' => 'jane@smith.com',
            'lgu_id' => $this->lgu->id
        ]);
    }

    /** @test */
    public function it_fails_if_lgu_id_missing_and_no_auth_context()
    {
        // Use a generic User instead of LguUser (assuming this user doesn't have lgu_id)
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@system.com',
            'password' => bcrypt('password')
        ]);

        Sanctum::actingAs($superAdmin);

        $response = $this->postJson('/api/lgu-users', [
            'first_name' => 'Bob',
            'last_name' => 'Jones',
            'email' => 'bob@jones.com'
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonFragment(['lgu_id' => ['The lgu id field is required when creating a user from this context.']]);
    }
}
