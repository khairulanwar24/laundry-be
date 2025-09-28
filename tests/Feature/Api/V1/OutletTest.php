<?php

namespace Tests\Feature\Api\V1;

use App\Models\Outlet;
use App\Models\User;
use App\Models\UserOutlet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OutletTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Get authorization headers for API requests.
     */
    private function getAuthHeaders(string $token): array
    {
        return [
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }

    public function test_user_can_create_outlet_as_owner(): void
    {
        // Create a user
        $user = User::factory()->create([
            'name' => 'John Owner',
            'email' => 'owner@test.com',
            'password' => Hash::make('password123'),
        ]);

        // Create token
        $token = $user->createToken('test-token')->plainTextToken;

        // Outlet data
        $outletData = [
            'name' => 'Laundry Segar',
            'address' => 'Jalan Mawar No. 123',
            'phone' => '081234567890',
        ];

        // Make request
        $response = $this->postJson('/api/v1/outlets', $outletData, $this->getAuthHeaders($token));

        // Assert response structure and content
        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'logo_path',
                    'address',
                    'phone',
                    'is_active',
                    'created_at',
                    'updated_at',
                    'user_role',
                    'user_permissions',
                ],
                'errors',
                'meta',
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Laundry Segar',
                    'address' => 'Jalan Mawar No. 123',
                    'phone' => '081234567890',
                    'is_active' => true,
                    'user_role' => 'owner',
                ],
                'errors' => null,
                'meta' => null,
            ]);

        // Verify outlet was created in database
        $this->assertDatabaseHas('outlets', [
            'name' => 'Laundry Segar',
            'address' => 'Jalan Mawar No. 123',
            'phone' => '081234567890',
            'is_active' => true,
        ]);

        // Verify user is assigned as owner
        $this->assertDatabaseHas('user_outlets', [
            'user_id' => $user->id,
            'role' => UserOutlet::ROLE_OWNER,
            'is_active' => true,
        ]);

        // Verify user has all permissions
        $userOutlet = UserOutlet::where('user_id', $user->id)->first();
        $this->assertTrue($userOutlet->permissions_json['create_order'] ?? false);
        $this->assertTrue($userOutlet->permissions_json['manage_employees'] ?? false);
    }

    public function test_member_can_list_their_outlets(): void
    {
        // Create user
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        // Create outlets
        $outlet1 = \Database\Factories\OutletFactory::new()->create(['name' => 'Outlet Alpha']);
        $outlet2 = \Database\Factories\OutletFactory::new()->create(['name' => 'Outlet Beta']);
        $outlet3 = \Database\Factories\OutletFactory::new()->create(['name' => 'Outlet Gamma']); // User not member

        // Add user to outlets 1 and 2
        UserOutlet::create([
            'user_id' => $user->id,
            'outlet_id' => $outlet1->id,
            'role' => UserOutlet::ROLE_OWNER,
            'permissions_json' => ['all' => true],
        ]);

        UserOutlet::create([
            'user_id' => $user->id,
            'outlet_id' => $outlet2->id,
            'role' => UserOutlet::ROLE_KARYAWAN,
            'permissions_json' => ['create_order' => true],
        ]);

        // Make request
        $response = $this->getJson('/api/v1/outlets', $this->getAuthHeaders($token));

        // Assert response
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'address',
                        'phone',
                        'is_active',
                        'user_role',
                        'user_permissions',
                    ],
                ],
                'errors',
                'meta',
            ])
            ->assertJson([
                'success' => true,
                'errors' => null,
                'meta' => null,
            ])
            ->assertJsonCount(2, 'data'); // Only outlets where user is member

        // Verify outlet names are in response
        $responseData = $response->json('data');
        $outletNames = collect($responseData)->pluck('name')->toArray();
        $this->assertContains('Outlet Alpha', $outletNames);
        $this->assertContains('Outlet Beta', $outletNames);
        $this->assertNotContains('Outlet Gamma', $outletNames);

        // Verify role and permissions are included
        $alphaOutlet = collect($responseData)->firstWhere('name', 'Outlet Alpha');
        $this->assertEquals('owner', $alphaOutlet['user_role']);
        $this->assertNotEmpty($alphaOutlet['user_permissions']);
    }

    public function test_owner_can_invite_employee_by_email_or_phone(): void
    {
        // Create owner and outlet
        $owner = User::factory()->create();
        $outlet = \Database\Factories\OutletFactory::new()->create();

        UserOutlet::create([
            'user_id' => $owner->id,
            'outlet_id' => $outlet->id,
            'role' => UserOutlet::ROLE_OWNER,
            'permissions_json' => ['all' => true],
        ]);

        $token = $owner->createToken('test-token')->plainTextToken;

        // Test invite by email
        $inviteData = [
            'name' => 'Budi Karyawan',
            'email' => 'budi@test.com',
            'phone' => '081234567890',
            'role' => 'karyawan',
            'permissions' => [
                'create_order' => true,
                'view_revenue' => false,
            ],
        ];

        $response = $this->postJson(
            "/api/v1/outlets/{$outlet->id}/invite",
            $inviteData,
            $this->getAuthHeaders($token)
        );

        // Assert response structure
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user_outlet' => [
                        'id',
                        'role',
                        'permissions_json',
                        'is_active',
                        'user' => [
                            'id',
                            'name',
                            'email',
                            'phone',
                            'is_active',
                        ],
                        'outlet' => [
                            'id',
                            'name',
                            'is_active',
                        ],
                    ],
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'phone',
                        'is_active',
                    ],
                ],
                'errors',
                'meta',
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Undangan berhasil dikirim',
                'data' => [
                    'user_outlet' => [
                        'role' => 'karyawan',
                        'is_active' => true,
                    ],
                    'user' => [
                        'name' => 'Budi Karyawan',
                        'email' => 'budi@test.com',
                        'phone' => '081234567890',
                    ],
                ],
                'errors' => null,
                'meta' => null,
            ]);

        // Verify user was created
        $this->assertDatabaseHas('users', [
            'name' => 'Budi Karyawan',
            'email' => 'budi@test.com',
            'phone' => '081234567890',
        ]);

        // Verify user_outlet relationship
        $user = User::where('email', 'budi@test.com')->first();
        $this->assertDatabaseHas('user_outlets', [
            'user_id' => $user->id,
            'outlet_id' => $outlet->id,
            'role' => 'karyawan',
            'is_active' => true,
        ]);

        // Verify permissions were set correctly
        $userOutlet = UserOutlet::where('user_id', $user->id)
            ->where('outlet_id', $outlet->id)
            ->first();
        $this->assertTrue($userOutlet->permissions_json['create_order']);
        $this->assertFalse($userOutlet->permissions_json['view_revenue']);
    }

    public function test_admin_can_update_member_permissions(): void
    {
        // Create owner, outlet, and employee
        $owner = User::factory()->create();
        $employee = User::factory()->create();
        $outlet = \Database\Factories\OutletFactory::new()->create();

        // Create user outlets
        UserOutlet::create([
            'user_id' => $owner->id,
            'outlet_id' => $outlet->id,
            'role' => UserOutlet::ROLE_OWNER,
            'permissions_json' => ['all' => true],
        ]);

        $userOutlet = UserOutlet::create([
            'user_id' => $employee->id,
            'outlet_id' => $outlet->id,
            'role' => UserOutlet::ROLE_KARYAWAN,
            'permissions_json' => ['create_order' => true],
        ]);

        $token = $owner->createToken('test-token')->plainTextToken;

        // Update member permissions
        $updateData = [
            'role' => 'karyawan',
            'permissions' => [
                'create_order' => true,
                'manage_customers' => true,
                'view_revenue' => true,
            ],
        ];

        $response = $this->putJson(
            "/api/v1/outlets/{$outlet->id}/members/{$userOutlet->id}",
            $updateData,
            $this->getAuthHeaders($token)
        );

        // Assert response
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'role',
                    'permissions_json',
                    'is_active',
                    'user',
                    'outlet',
                ],
                'errors',
                'meta',
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Member berhasil diperbarui',
                'data' => [
                    'role' => 'karyawan',
                    'is_active' => true,
                ],
                'errors' => null,
                'meta' => null,
            ]);

        // Verify database update
        $userOutlet->refresh();
        $this->assertEquals('karyawan', $userOutlet->role);
        $this->assertTrue($userOutlet->permissions_json['create_order']);
        $this->assertTrue($userOutlet->permissions_json['manage_customers']);
        $this->assertTrue($userOutlet->permissions_json['view_revenue']);
    }

    public function test_non_member_cannot_access_outlet(): void
    {
        // Create users and outlet
        $nonMember = User::factory()->create();
        $outlet = \Database\Factories\OutletFactory::new()->create();

        $token = $nonMember->createToken('test-token')->plainTextToken;

        // Try to access outlet details
        $response = $this->getJson(
            "/api/v1/outlets/{$outlet->id}",
            $this->getAuthHeaders($token)
        );

        // Should be forbidden
        $response->assertStatus(403)
            ->assertJsonStructure([
                'success',
                'message',
            ])
            ->assertJson([
                'success' => false,
                'message' => 'Anda tidak terdaftar di outlet ini',
            ]);

        // Try to update outlet
        $response = $this->putJson(
            "/api/v1/outlets/{$outlet->id}",
            ['name' => 'Updated Name'],
            $this->getAuthHeaders($token)
        );

        $response->assertStatus(403);

        // Try to invite someone
        $response = $this->postJson(
            "/api/v1/outlets/{$outlet->id}/invite",
            [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'role' => 'karyawan',
            ],
            $this->getAuthHeaders($token)
        );

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_access_outlets(): void
    {
        $outlet = \Database\Factories\OutletFactory::new()->create();

        // Try to list outlets without authentication
        $response = $this->getJson('/api/v1/outlets');
        $response->assertStatus(401);

        // Try to create outlet without authentication
        $response = $this->postJson('/api/v1/outlets', [
            'name' => 'Test Outlet',
            'address' => 'Test Address',
        ]);
        $response->assertStatus(401);

        // Try to access specific outlet without authentication
        $response = $this->getJson("/api/v1/outlets/{$outlet->id}");
        $response->assertStatus(401);
    }

    public function test_validation_errors_return_proper_envelope(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        // Try to create outlet without required fields
        $response = $this->postJson(
            '/api/v1/outlets',
            ['name' => ''], // Empty name
            $this->getAuthHeaders($token)
        );

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'data',
                'errors' => [
                    'name',
                ],
                'meta',
            ])
            ->assertJson([
                'success' => false,
                'message' => 'Validasi gagal',
                'data' => null,
                'meta' => null,
            ]);

        $this->assertIsArray($response->json('errors.name'));
    }
}
