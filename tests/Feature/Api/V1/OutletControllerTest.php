<?php

namespace Tests\Feature\Api\V1;

use App\Models\Outlet;
use App\Models\User;
use App\Models\UserOutlet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OutletControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $employee;

    private Outlet $outlet;

    protected function setUp(): void
    {
        parent::setUp();

        // Create owner and outlet
        $this->owner = User::factory()->create([
            'name' => 'Test Owner',
            'email' => 'owner@test.com',
            'password' => Hash::make('password123'),
        ]);

        // Create employee
        $this->employee = User::factory()->create([
            'name' => 'Test Employee',
            'email' => 'employee@test.com',
            'password' => Hash::make('password123'),
        ]);

        // Create outlet with owner
        $this->outlet = \Database\Factories\OutletFactory::new()->create([
            'name' => 'Test Outlet',
            'address' => 'Test Address',
        ]);

        UserOutlet::create([
            'user_id' => $this->owner->id,
            'outlet_id' => $this->outlet->id,
            'role' => UserOutlet::ROLE_OWNER,
            'permissions_json' => ['all' => true],
        ]);
    }

    public function test_index_returns_user_outlets(): void
    {
        Sanctum::actingAs($this->owner);

        $response = $this->getJson(route('api.v1.outlets.index'));

        $response->assertOk()
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
                        'created_at',
                        'updated_at',
                    ],
                ],
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.name', 'Test Outlet');
    }

    public function test_store_creates_outlet_for_owner(): void
    {
        Sanctum::actingAs($this->owner);

        $outletData = [
            'name' => 'New Outlet',
            'address' => 'New Address',
            'phone' => '081234567890',
        ];

        $response = $this->postJson(route('api.v1.outlets.store'), $outletData);

        $response->assertCreated()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'address',
                    'phone',
                    'is_active',
                ],
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'New Outlet');

        $this->assertDatabaseHas('outlets', $outletData);
        $this->assertDatabaseHas('user_outlets', [
            'user_id' => $this->owner->id,
            'role' => UserOutlet::ROLE_OWNER,
        ]);
    }

    public function test_store_requires_authentication(): void
    {
        $response = $this->postJson(route('api.v1.outlets.store'), [
            'name' => 'New Outlet',
            'address' => 'New Address',
        ]);

        $response->assertUnauthorized();
    }

    public function test_show_returns_outlet_details(): void
    {
        Sanctum::actingAs($this->owner);

        $response = $this->getJson(route('api.v1.outlets.show', $this->outlet));

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'address',
                    'phone',
                    'is_active',
                    'user_role',
                    'user_permissions',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $this->outlet->id);
    }

    public function test_show_requires_authorization(): void
    {
        Sanctum::actingAs($this->employee);

        $response = $this->getJson(route('api.v1.outlets.show', $this->outlet));

        $response->assertForbidden();
    }

    public function test_update_modifies_outlet(): void
    {
        Sanctum::actingAs($this->owner);

        $updateData = [
            'name' => 'Updated Outlet',
            'address' => 'Updated Address',
            'phone' => '089876543210',
        ];

        $response = $this->putJson(route('api.v1.outlets.update', $this->outlet), $updateData);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Updated Outlet');

        $this->assertDatabaseHas('outlets', array_merge(['id' => $this->outlet->id], $updateData));
    }

    public function test_invite_creates_user_outlet(): void
    {
        Sanctum::actingAs($this->owner);

        $inviteData = [
            'name' => 'New Employee',
            'email' => 'new.employee@test.com',
            'phone' => '081111111111',
            'role' => UserOutlet::ROLE_KARYAWAN,
            'permissions' => ['view_transactions' => true],
        ];

        $response = $this->postJson(route('api.v1.outlets.invite', $this->outlet), $inviteData);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user_outlet' => [
                        'id',
                        'role',
                        'permissions_json',
                        'user',
                        'outlet',
                    ],
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'phone',
                        'is_active',
                    ],
                ],
            ])
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('users', [
            'email' => 'new.employee@test.com',
            'name' => 'New Employee',
        ]);

        $this->assertDatabaseHas('user_outlets', [
            'outlet_id' => $this->outlet->id,
            'role' => UserOutlet::ROLE_KARYAWAN,
        ]);
    }

    public function test_invite_requires_invite_permission(): void
    {
        // Add employee to outlet but without invite permission
        UserOutlet::create([
            'user_id' => $this->employee->id,
            'outlet_id' => $this->outlet->id,
            'role' => UserOutlet::ROLE_KARYAWAN,
            'permissions_json' => ['create_order' => true],
        ]);

        Sanctum::actingAs($this->employee);

        $response = $this->postJson(route('api.v1.outlets.invite', $this->outlet), [
            'name' => 'Another Employee',
            'email' => 'another@test.com',
            'phone' => '08123456789',
            'role' => UserOutlet::ROLE_KARYAWAN,
        ]);

        // Employee is a member of the outlet but lacks invite permission
        // Controller catches authorization exception and returns 400 error
        $response->assertStatus(400);
        $this->assertStringContainsString('Gagal mengundang user', $response->json('message'));
    }

    public function test_update_member_changes_role_and_permissions(): void
    {
        // Add employee to outlet
        $userOutlet = UserOutlet::create([
            'user_id' => $this->employee->id,
            'outlet_id' => $this->outlet->id,
            'role' => UserOutlet::ROLE_KARYAWAN,
            'permissions_json' => ['create_order' => true],
        ]);

        Sanctum::actingAs($this->owner);

        $updateData = [
            'role' => UserOutlet::ROLE_KARYAWAN,
            'permissions' => [
                'create_order' => true,
                'view_revenue' => true,
            ],
        ];

        $response = $this->putJson(
            route('api.v1.outlets.members.update', [$this->outlet, $userOutlet]),
            $updateData
        );

        $response->assertOk()
            ->assertJsonPath('success', true);

        $userOutlet->refresh();
        $this->assertEquals($updateData['role'], $userOutlet->role);
        $permissions = $userOutlet->permissions_json;
        $this->assertTrue($permissions['create_order'] ?? false);
    }

    public function test_remove_member_deactivates_user_outlet(): void
    {
        // Add employee to outlet
        $userOutlet = UserOutlet::create([
            'user_id' => $this->employee->id,
            'outlet_id' => $this->outlet->id,
            'role' => UserOutlet::ROLE_KARYAWAN,
            'permissions_json' => ['create_order' => true],
        ]);

        Sanctum::actingAs($this->owner);

        $response = $this->deleteJson(
            route('api.v1.outlets.members.remove', [$this->outlet, $userOutlet])
        );

        $response->assertOk()
            ->assertJsonPath('success', true);

        $userOutlet->refresh();
        $this->assertFalse($userOutlet->is_active);
    }

    public function test_remove_member_prevents_removing_last_owner(): void
    {
        $ownerUserOutlet = UserOutlet::where('user_id', $this->owner->id)
            ->where('outlet_id', $this->outlet->id)
            ->first();

        Sanctum::actingAs($this->owner);

        $response = $this->deleteJson(
            route('api.v1.outlets.members.remove', [$this->outlet, $ownerUserOutlet])
        );

        $response->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Tidak dapat menghapus owner terakhir');
    }

    public function test_validation_errors_return_proper_format(): void
    {
        Sanctum::actingAs($this->owner);

        $response = $this->postJson(route('api.v1.outlets.store'), [
            'name' => '', // Required field
        ]);

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
            ->assertJsonPath('success', false);
    }

    public function test_unauthenticated_requests_return_proper_format(): void
    {
        $response = $this->getJson(route('api.v1.outlets.index'));

        $response->assertUnauthorized()
            ->assertJsonStructure([
                'message',
            ]);
    }
}
