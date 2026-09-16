<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_register_as_a_student(): void
    {
        $response = $this->withHeader('Referer', 'http://localhost:5173/')->postJson('/api/register', [
            'name' => 'Nueva Alumna',
            'email' => 'nueva@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'student',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('users', ['email' => 'nueva@example.com', 'role' => 'student']);
    }

    public function test_registration_cannot_grant_admin_role(): void
    {
        $response = $this->withHeader('Referer', 'http://localhost:5173/')->postJson('/api/register', [
            'name' => 'Intento Admin',
            'email' => 'intento@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'admin',
        ]);

        $response->assertUnprocessable();
    }

    public function test_a_user_can_login_with_correct_credentials(): void
    {
        $user = User::factory()->create(['password' => bcrypt('secret123')]);

        $response = $this->withHeader('Referer', 'http://localhost:5173/')->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'secret123',
        ]);

        $response->assertOk()->assertJsonPath('user.id', $user->id);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $user = User::factory()->create(['password' => bcrypt('secret123')]);

        $response = $this->withHeader('Referer', 'http://localhost:5173/')->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertUnprocessable();
    }

    public function test_authenticated_user_can_fetch_their_profile(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->getJson('/api/me')->assertOk()->assertJsonPath('user.id', $user->id);
    }
}
