<?php

namespace Tests\Feature;

use App\Models\Outlet;
use App\Models\User;
use App\Models\UserOutlet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OutletMiddlewareIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private function bearerToken(string $token): array
    {
        return ['Authorization' => "Bearer {$token}"];
    }

    public function test_authenticated_outlet_member_can_access_outlet_routes(): void
    {
        $user = User::factory()->create();
        $outlet = \Database\Factories\OutletFactory::new()->create();

        UserOutlet::factory()->create([
            'user_id' => $user->id,
            'outlet_id' => $outlet->id,
            'role' => UserOutlet::ROLE_OWNER,
            'is_active' => true,
        ]);

        $token = $user->createToken('test')->plainTextToken;

        $response = $this->getJson("/api/v1/outlets/{$outlet->id}/test", $this->bearerToken($token));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Access granted to outlet',
            ]);
    }

    public function test_authenticated_non_member_cannot_access_outlet_routes(): void
    {
        $user = User::factory()->create();
        $outlet = \Database\Factories\OutletFactory::new()->create();
        // User is not a member of this outlet

        $token = $user->createToken('test')->plainTextToken;

        $response = $this->getJson("/api/v1/outlets/{$outlet->id}/test", $this->bearerToken($token));

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Anda tidak terdaftar di outlet ini',
            ]);
    }

    public function test_authenticated_inactive_member_cannot_access_outlet_routes(): void
    {
        $user = User::factory()->create();
        $outlet = \Database\Factories\OutletFactory::new()->create();

        UserOutlet::factory()->create([
            'user_id' => $user->id,
            'outlet_id' => $outlet->id,
            'role' => UserOutlet::ROLE_KARYAWAN,
            'is_active' => false, // Inactive member
        ]);

        $token = $user->createToken('test')->plainTextToken;

        $response = $this->getJson("/api/v1/outlets/{$outlet->id}/test", $this->bearerToken($token));

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Anda tidak terdaftar di outlet ini',
            ]);
    }

    public function test_unauthenticated_user_cannot_access_outlet_routes(): void
    {
        $outlet = \Database\Factories\OutletFactory::new()->create();

        $response = $this->getJson("/api/v1/outlets/{$outlet->id}/test");

        $response->assertStatus(401); // Should be blocked by auth:sanctum first
    }

    public function test_outlet_middleware_with_non_existent_outlet(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->getJson('/api/v1/outlets/99999/test', $this->bearerToken($token));

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Outlet tidak ditemukan',
            ]);
    }

    public function test_karyawan_can_access_outlet_routes(): void
    {
        $user = User::factory()->create();
        $outlet = \Database\Factories\OutletFactory::new()->create();

        UserOutlet::factory()->create([
            'user_id' => $user->id,
            'outlet_id' => $outlet->id,
            'role' => UserOutlet::ROLE_KARYAWAN,
            'is_active' => true,
        ]);

        $token = $user->createToken('test')->plainTextToken;

        $response = $this->getJson("/api/v1/outlets/{$outlet->id}/test", $this->bearerToken($token));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Access granted to outlet',
            ]);
    }
}
