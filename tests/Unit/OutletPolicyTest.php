<?php

namespace Tests\Unit;

use App\Models\Outlet;
use App\Models\User;
use App\Models\UserOutlet;
use App\Policies\OutletPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OutletPolicyTest extends TestCase
{
    use RefreshDatabase;

    private OutletPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new OutletPolicy;
    }

    public function test_view_allows_outlet_member(): void
    {
        $user = User::factory()->create();
        $outlet = \Database\Factories\OutletFactory::new()->create();

        // Create user-outlet relationship as karyawan
        UserOutlet::factory()->create([
            'user_id' => $user->id,
            'outlet_id' => $outlet->id,
            'role' => UserOutlet::ROLE_KARYAWAN,
            'is_active' => true,
        ]);

        $this->assertTrue($this->policy->view($user, $outlet));
    }

    public function test_view_allows_outlet_owner(): void
    {
        $user = User::factory()->create();
        $outlet = \Database\Factories\OutletFactory::new()->create();

        // Create user-outlet relationship as owner
        UserOutlet::factory()->create([
            'user_id' => $user->id,
            'outlet_id' => $outlet->id,
            'role' => UserOutlet::ROLE_OWNER,
            'is_active' => true,
        ]);

        $this->assertTrue($this->policy->view($user, $outlet));
    }

    public function test_view_denies_non_member(): void
    {
        $user = User::factory()->create();
        $outlet = \Database\Factories\OutletFactory::new()->create();

        // User is not associated with the outlet
        $this->assertFalse($this->policy->view($user, $outlet));
    }

    public function test_view_denies_inactive_member(): void
    {
        $user = User::factory()->create();
        $outlet = \Database\Factories\OutletFactory::new()->create();

        // Create inactive user-outlet relationship
        UserOutlet::factory()->create([
            'user_id' => $user->id,
            'outlet_id' => $outlet->id,
            'role' => UserOutlet::ROLE_KARYAWAN,
            'is_active' => false,
        ]);

        $this->assertFalse($this->policy->view($user, $outlet));
    }

    public function test_update_allows_owner(): void
    {
        $user = User::factory()->create();
        $outlet = \Database\Factories\OutletFactory::new()->create();

        UserOutlet::factory()->create([
            'user_id' => $user->id,
            'outlet_id' => $outlet->id,
            'role' => UserOutlet::ROLE_OWNER,
            'is_active' => true,
        ]);

        $this->assertTrue($this->policy->update($user, $outlet));
    }

    public function test_update_denies_karyawan(): void
    {
        $user = User::factory()->create();
        $outlet = \Database\Factories\OutletFactory::new()->create();

        UserOutlet::factory()->create([
            'user_id' => $user->id,
            'outlet_id' => $outlet->id,
            'role' => UserOutlet::ROLE_KARYAWAN,
            'is_active' => true,
        ]);

        $this->assertFalse($this->policy->update($user, $outlet));
    }

    public function test_update_denies_non_member(): void
    {
        $user = User::factory()->create();
        $outlet = \Database\Factories\OutletFactory::new()->create();

        $this->assertFalse($this->policy->update($user, $outlet));
    }

    public function test_update_denies_inactive_owner(): void
    {
        $user = User::factory()->create();
        $outlet = \Database\Factories\OutletFactory::new()->create();

        UserOutlet::factory()->create([
            'user_id' => $user->id,
            'outlet_id' => $outlet->id,
            'role' => UserOutlet::ROLE_OWNER,
            'is_active' => false,
        ]);

        $this->assertFalse($this->policy->update($user, $outlet));
    }

    public function test_invite_allows_owner(): void
    {
        $user = User::factory()->create();
        $outlet = \Database\Factories\OutletFactory::new()->create();

        UserOutlet::factory()->create([
            'user_id' => $user->id,
            'outlet_id' => $outlet->id,
            'role' => UserOutlet::ROLE_OWNER,
            'is_active' => true,
        ]);

        $this->assertTrue($this->policy->invite($user, $outlet));
    }

    public function test_invite_denies_karyawan(): void
    {
        $user = User::factory()->create();
        $outlet = \Database\Factories\OutletFactory::new()->create();

        UserOutlet::factory()->create([
            'user_id' => $user->id,
            'outlet_id' => $outlet->id,
            'role' => UserOutlet::ROLE_KARYAWAN,
            'is_active' => true,
        ]);

        $this->assertFalse($this->policy->invite($user, $outlet));
    }

    public function test_invite_denies_non_member(): void
    {
        $user = User::factory()->create();
        $outlet = \Database\Factories\OutletFactory::new()->create();

        $this->assertFalse($this->policy->invite($user, $outlet));
    }

    public function test_multiple_outlets_access(): void
    {
        $user = User::factory()->create();
        $outlet1 = \Database\Factories\OutletFactory::new()->create();
        $outlet2 = \Database\Factories\OutletFactory::new()->create();

        // User is owner of outlet1
        UserOutlet::factory()->create([
            'user_id' => $user->id,
            'outlet_id' => $outlet1->id,
            'role' => UserOutlet::ROLE_OWNER,
            'is_active' => true,
        ]);

        // User is karyawan of outlet2
        UserOutlet::factory()->create([
            'user_id' => $user->id,
            'outlet_id' => $outlet2->id,
            'role' => UserOutlet::ROLE_KARYAWAN,
            'is_active' => true,
        ]);

        // Should be able to view both outlets
        $this->assertTrue($this->policy->view($user, $outlet1));
        $this->assertTrue($this->policy->view($user, $outlet2));

        // Should only be able to update/invite for outlet1 (owner)
        $this->assertTrue($this->policy->update($user, $outlet1));
        $this->assertFalse($this->policy->update($user, $outlet2));

        $this->assertTrue($this->policy->invite($user, $outlet1));
        $this->assertFalse($this->policy->invite($user, $outlet2));
    }
}
