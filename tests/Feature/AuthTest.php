<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    // ────────────────────────────────────────────
    // Helpers
    // ────────────────────────────────────────────

    private function assertValidationError($response, string $field): void
    {
        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_ERROR')
            ->assertJsonStructure(['error' => ['details' => [$field]]]);
    }

    // ────────────────────────────────────────────
    // Register
    // ────────────────────────────────────────────

    public function test_user_can_register_with_valid_data(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['token', 'user' => ['id', 'name', 'email']],
                'message',
                'meta',
            ])
            ->assertJsonPath('data.user.email', 'john@example.com')
            ->assertJsonPath('message', 'Registration successful.');

        $this->assertDatabaseHas('users', ['email' => 'john@example.com']);
    }

    public function test_register_returns_a_usable_token(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Token Test',
            'email' => 'token@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $token = $response->json('data.token');
        $this->assertNotNull($token);

        // Token can access a protected route
        $this->getJson('/api/v1/user', [
            'Authorization' => "Bearer {$token}",
        ])->assertOk();
    }

    public function test_register_requires_name(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->assertValidationError($response, 'name');
    }

    public function test_register_requires_valid_email(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'John Doe',
            'email' => 'not-an-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->assertValidationError($response, 'email');
    }

    public function test_register_requires_unique_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Another User',
            'email' => 'taken@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->assertValidationError($response, 'email');
    }

    public function test_register_requires_password_min_8_chars(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $this->assertValidationError($response, 'password');
    }

    public function test_register_requires_password_confirmation(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            // password_confirmation missing
        ]);

        $this->assertValidationError($response, 'password');
    }

    public function test_register_requires_matching_password_confirmation(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different456',
        ]);

        $this->assertValidationError($response, 'password');
    }

    // ────────────────────────────────────────────
    // Login
    // ────────────────────────────────────────────

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'jane@example.com',
            'password' => bcrypt('secret123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'jane@example.com',
            'password' => 'secret123',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['token', 'user' => ['id', 'name', 'email']],
                'message',
                'meta',
            ])
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('message', 'Login successful.');
    }

    public function test_login_returns_usable_token(): void
    {
        User::factory()->create([
            'email' => 'jane@example.com',
            'password' => bcrypt('secret123'),
        ]);

        $token = $this->postJson('/api/v1/auth/login', [
            'email' => 'jane@example.com',
            'password' => 'secret123',
        ])->json('data.token');

        $this->getJson('/api/v1/user', [
            'Authorization' => "Bearer {$token}",
        ])->assertOk();
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create(['email' => 'jane@example.com']);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'jane@example.com',
            'password' => 'wrongpassword',
        ])->assertStatus(401)
            ->assertJsonPath('error.code', 'INVALID_CREDENTIALS');
    }

    public function test_login_fails_with_unknown_email(): void
    {
        $this->postJson('/api/v1/auth/login', [
            'email' => 'nobody@example.com',
            'password' => 'password123',
        ])->assertStatus(401)
            ->assertJsonPath('error.code', 'INVALID_CREDENTIALS');
    }

    public function test_login_requires_email(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'password' => 'password123',
        ]);

        $this->assertValidationError($response, 'email');
    }

    public function test_login_requires_password(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'jane@example.com',
        ]);

        $this->assertValidationError($response, 'password');
    }

    public function test_login_requires_valid_email_format(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'not-an-email',
            'password' => 'password123',
        ]);

        $this->assertValidationError($response, 'email');
    }

    // ────────────────────────────────────────────
    // Logout
    // ────────────────────────────────────────────

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/auth/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Logged out successfully.');
    }

    public function test_unauthenticated_user_cannot_logout(): void
    {
        $this->postJson('/api/v1/auth/logout')
            ->assertStatus(401);
    }

    public function test_token_is_revoked_after_logout(): void
    {
        $user = User::factory()->create();

        $tokenResult = $user->createToken('api-token');
        $plainToken = $tokenResult->plainTextToken;
        $tokenId = $tokenResult->accessToken->id;

        $this->withToken($plainToken)
            ->postJson('/api/v1/auth/logout')
            ->assertOk();

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $tokenId]);
    }
}





