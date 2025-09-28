<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private function bearerToken(string $token): array
    {
        return ['Authorization' => "Bearer {$token}"];
    }

    private function userPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '081234567890',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'device_name' => 'mobile_test',
            'is_active' => true,
        ], $overrides);
    }

    public function test_can_register_success(): void
    {
        $payload = $this->userPayload();

        $response = $this->postJson('/api/v1/auth/register', $payload);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'phone',
                        'is_active',
                        'created_at',
                        'updated_at',
                    ],
                    'token',
                ],
                'errors',
                'meta',
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Created',
                'data' => [
                    'user' => [
                        'name' => 'John Doe',
                        'email' => 'john@example.com',
                        'phone' => '081234567890',
                        'is_active' => true,
                    ],
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'name' => 'John Doe',
            'phone' => '081234567890',
            'is_active' => true,
        ]);

        $this->assertIsString($response->json('data.token'));
    }

    public function test_cannot_register_duplicate_email(): void
    {
        User::factory()->create(['email' => 'john@example.com']);

        $payload = $this->userPayload(['email' => 'john@example.com']);

        $response = $this->postJson('/api/v1/auth/register', $payload);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'data',
                'errors',
                'meta',
            ])
            ->assertJson([
                'success' => false,
            ]);

        $this->assertArrayHasKey('email', $response->json('errors'));
    }

    public function test_can_login_success(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $payload = [
            'email' => 'john@example.com',
            'password' => 'password123',
            'device_name' => 'mobile_test',
        ];

        $response = $this->postJson('/api/v1/auth/login', $payload);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'phone',
                        'is_active',
                        'created_at',
                        'updated_at',
                    ],
                    'token',
                ],
                'errors',
                'meta',
            ])
            ->assertJson([
                'success' => true,
                'message' => 'OK',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'email' => 'john@example.com',
                        'is_active' => true,
                    ],
                ],
            ]);

        $this->assertIsString($response->json('data.token'));
    }

    public function test_cannot_login_wrong_password(): void
    {
        User::factory()->create([
            'email' => 'john@example.com',
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $payload = [
            'email' => 'john@example.com',
            'password' => 'wrongpassword',
            'device_name' => 'mobile_test',
        ];

        $response = $this->postJson('/api/v1/auth/login', $payload);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Email atau password salah',
                'data' => null,
                'errors' => [],
                'meta' => null,
            ]);
    }

    public function test_cannot_login_with_inactive_user(): void
    {
        User::factory()->create([
            'email' => 'john@example.com',
            'password' => bcrypt('password123'),
            'is_active' => false,
        ]);

        $payload = [
            'email' => 'john@example.com',
            'password' => 'password123',
            'device_name' => 'mobile_test',
        ];

        $response = $this->postJson('/api/v1/auth/login', $payload);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Akun tidak aktif',
                'data' => null,
                'errors' => [],
                'meta' => null,
            ]);
    }

    public function test_me_requires_token(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(401);
    }

    public function test_can_get_me_with_valid_token(): void
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'is_active' => true,
        ]);

        $token = $user->createToken('test_device')->plainTextToken;

        $response = $this->getJson('/api/v1/auth/me', $this->bearerToken($token));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'phone',
                        'is_active',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'errors',
                'meta',
            ])
            ->assertJson([
                'success' => true,
                'message' => 'OK',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => 'John Doe',
                        'email' => 'john@example.com',
                        'is_active' => true,
                    ],
                ],
            ]);
    }

    public function test_can_logout_and_token_revoked(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $token = $user->createToken('test_device')->plainTextToken;

        // Verify token works before logout
        $response = $this->getJson('/api/v1/auth/me', $this->bearerToken($token));
        $response->assertStatus(200);

        // Logout
        $response = $this->postJson('/api/v1/auth/logout', [], $this->bearerToken($token));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Logged out',
                'data' => null,
                'errors' => null,
                'meta' => null,
            ]);

        // Verify token is revoked after logout by checking that the user has no tokens
        $this->assertCount(0, $user->fresh()->tokens);
    }
}
