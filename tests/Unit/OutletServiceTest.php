<?php

namespace Tests\Unit;

use App\Domain\Permissions\OutletPermissions;
use App\Models\Outlet;
use App\Models\User;
use App\Models\UserOutlet;
use App\Services\Api\V1\OutletService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OutletServiceTest extends TestCase
{
    use RefreshDatabase;

    private OutletService $outletService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->outletService = new OutletService;
    }

    public function test_create_for_owner_creates_outlet_and_assigns_owner(): void
    {
        $owner = User::factory()->create(['is_active' => true]);
        $data = [
            'name' => 'Test Outlet',
            'address' => 'Test Address',
            'phone' => '081234567890',
        ];

        $outlet = $this->outletService->createForOwner($owner, $data);

        $this->assertInstanceOf(Outlet::class, $outlet);
        $this->assertEquals($owner->id, $outlet->owner_user_id);
        $this->assertEquals('Test Outlet', $outlet->name);
        $this->assertTrue($outlet->is_active);

        // Check that owner is assigned to outlet
        $this->assertDatabaseHas('user_outlets', [
            'user_id' => $owner->id,
            'outlet_id' => $outlet->id,
            'role' => UserOutlet::ROLE_OWNER,
            'is_active' => true,
        ]);

        // Check that owner has all permissions
        $userOutlet = UserOutlet::where('user_id', $owner->id)
            ->where('outlet_id', $outlet->id)
            ->first();

        $expectedPermissions = OutletPermissions::defaultsFor('owner');
        $this->assertEquals($expectedPermissions, $userOutlet->permissions_json);
    }

    public function test_create_for_owner_throws_exception_for_inactive_user(): void
    {
        $owner = User::factory()->create(['is_active' => false]);
        $data = ['name' => 'Test Outlet'];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('User must be active to create outlet');

        $this->outletService->createForOwner($owner, $data);
    }

    public function test_assign_user_creates_user_outlet_relationship(): void
    {
        $outlet = \Database\Factories\OutletFactory::new()->create(['is_active' => true]);
        $user = User::factory()->create(['is_active' => true]);

        $userOutlet = $this->outletService->assignUser($outlet, $user, UserOutlet::ROLE_KARYAWAN);

        $this->assertInstanceOf(UserOutlet::class, $userOutlet);
        $this->assertEquals($user->id, $userOutlet->user_id);
        $this->assertEquals($outlet->id, $userOutlet->outlet_id);
        $this->assertEquals(UserOutlet::ROLE_KARYAWAN, $userOutlet->role);
        $this->assertTrue($userOutlet->is_active);

        // Check permissions are set correctly for karyawan
        $expectedPermissions = OutletPermissions::defaultsFor('karyawan');
        $this->assertEquals($expectedPermissions, $userOutlet->permissions_json);
    }

    public function test_assign_user_with_permission_override(): void
    {
        $outlet = \Database\Factories\OutletFactory::new()->create(['is_active' => true]);
        $user = User::factory()->create(['is_active' => true]);
        $permOverride = [
            OutletPermissions::VIEW_REVENUE => true,
            OutletPermissions::MANAGE_CUSTOMERS => false,
        ];

        $userOutlet = $this->outletService->assignUser($outlet, $user, UserOutlet::ROLE_KARYAWAN, $permOverride);

        $basePermissions = OutletPermissions::defaultsFor('karyawan');
        $expectedPermissions = OutletPermissions::merge($basePermissions, $permOverride);

        $this->assertEquals($expectedPermissions, $userOutlet->permissions_json);
        $this->assertTrue($userOutlet->permissions_json[OutletPermissions::VIEW_REVENUE]);
        $this->assertFalse($userOutlet->permissions_json[OutletPermissions::MANAGE_CUSTOMERS]);
    }

    public function test_assign_user_updates_existing_relationship(): void
    {
        $outlet = \Database\Factories\OutletFactory::new()->create(['is_active' => true]);
        $user = User::factory()->create(['is_active' => true]);

        // Create initial relationship
        $initialUserOutlet = UserOutlet::factory()->create([
            'user_id' => $user->id,
            'outlet_id' => $outlet->id,
            'role' => UserOutlet::ROLE_KARYAWAN,
        ]);

        // Update with new role
        $updatedUserOutlet = $this->outletService->assignUser($outlet, $user, UserOutlet::ROLE_OWNER);

        $this->assertEquals($initialUserOutlet->id, $updatedUserOutlet->id);
        $this->assertEquals(UserOutlet::ROLE_OWNER, $updatedUserOutlet->role);

        // Should only have one record
        $this->assertEquals(1, UserOutlet::where('user_id', $user->id)
            ->where('outlet_id', $outlet->id)
            ->count());
    }

    public function test_assign_user_throws_exception_for_inactive_outlet(): void
    {
        $outlet = \Database\Factories\OutletFactory::new()->create(['is_active' => false]);
        $user = User::factory()->create(['is_active' => true]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Cannot assign user to inactive outlet');

        $this->outletService->assignUser($outlet, $user, UserOutlet::ROLE_KARYAWAN);
    }

    public function test_assign_user_throws_exception_for_inactive_user(): void
    {
        $outlet = \Database\Factories\OutletFactory::new()->create(['is_active' => true]);
        $user = User::factory()->create(['is_active' => false]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Cannot assign inactive user to outlet');

        $this->outletService->assignUser($outlet, $user, UserOutlet::ROLE_KARYAWAN);
    }

    public function test_assign_user_throws_exception_for_invalid_role(): void
    {
        $outlet = \Database\Factories\OutletFactory::new()->create(['is_active' => true]);
        $user = User::factory()->create(['is_active' => true]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid role specified');

        $this->outletService->assignUser($outlet, $user, 'invalid_role');
    }
}
