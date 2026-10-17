<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'me@afif.dev',
            'password' => Hash::make('password'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'me@afif.dev',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'token',
                'token_type',
                'user' => [
                    'id',
                    'name',
                    'email',
                ],
            ]);
    }

    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'me@afif.dev',
            'password' => Hash::make('password'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'me@afif.dev',
            'password' => 'nopassword',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Invalid credentials.',
            ]);
    }

    public function test_login_validation_fails_when_fields_are_missing(): void
    {
        $response = $this->postJson('/api/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/logout');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Logged out successfully.',
            ]);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_unauthenticated_user_cannot_logout(): void
    {
        $response = $this->postJson('/api/logout');

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }
}
