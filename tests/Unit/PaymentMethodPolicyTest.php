<?php

namespace Tests\Unit;

use App\Models\Outlet;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Models\UserOutlet;
use App\Policies\PaymentMethodPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentMethodPolicyTest extends TestCase
{
    use RefreshDatabase;

    private PaymentMethodPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new PaymentMethodPolicy;
    }

    public function test_view_any_allows_outlet_member(): void
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

        $this->assertTrue($this->policy->viewAny($user, $outlet));
    }

    public function test_view_any_allows_outlet_owner(): void
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

        $this->assertTrue($this->policy->viewAny($user, $outlet));
    }

    public function test_view_any_denies_non_member(): void
    {
        $user = User::factory()->create();
        $outlet = \Database\Factories\OutletFactory::new()->create();

        $this->assertFalse($this->policy->viewAny($user, $outlet));
    }

    public function test_view_any_denies_inactive_member(): void
    {
        $user = User::factory()->create();
        $outlet = \Database\Factories\OutletFactory::new()->create();

        UserOutlet::factory()->create([
            'user_id' => $user->id,
            'outlet_id' => $outlet->id,
            'role' => UserOutlet::ROLE_KARYAWAN,
            'is_active' => false,
        ]);

        $this->assertFalse($this->policy->viewAny($user, $outlet));
    }

    public function test_create_allows_owner(): void
    {
        $user = User::factory()->create();
        $outlet = \Database\Factories\OutletFactory::new()->create();

        UserOutlet::factory()->create([
            'user_id' => $user->id,
            'outlet_id' => $outlet->id,
            'role' => UserOutlet::ROLE_OWNER,
            'is_active' => true,
        ]);

        $this->assertTrue($this->policy->create($user, $outlet));
    }

    public function test_create_denies_karyawan(): void
    {
        $user = User::factory()->create();
        $outlet = \Database\Factories\OutletFactory::new()->create();

        UserOutlet::factory()->create([
            'user_id' => $user->id,
            'outlet_id' => $outlet->id,
            'role' => UserOutlet::ROLE_KARYAWAN,
            'is_active' => true,
        ]);

        $this->assertFalse($this->policy->create($user, $outlet));
    }

    public function test_create_denies_non_member(): void
    {
        $user = User::factory()->create();
        $outlet = \Database\Factories\OutletFactory::new()->create();

        $this->assertFalse($this->policy->create($user, $outlet));
    }

    public function test_update_allows_owner_of_payment_method_outlet(): void
    {
        $user = User::factory()->create();
        $outlet = \Database\Factories\OutletFactory::new()->create();
        $paymentMethod = PaymentMethod::factory()->create(['outlet_id' => $outlet->id]);

        UserOutlet::factory()->create([
            'user_id' => $user->id,
            'outlet_id' => $outlet->id,
            'role' => UserOutlet::ROLE_OWNER,
            'is_active' => true,
        ]);

        $this->assertTrue($this->policy->update($user, $paymentMethod));
    }

    public function test_update_denies_karyawan_of_payment_method_outlet(): void
    {
        $user = User::factory()->create();
        $outlet = \Database\Factories\OutletFactory::new()->create();
        $paymentMethod = PaymentMethod::factory()->create(['outlet_id' => $outlet->id]);

        UserOutlet::factory()->create([
            'user_id' => $user->id,
            'outlet_id' => $outlet->id,
            'role' => UserOutlet::ROLE_KARYAWAN,
            'is_active' => true,
        ]);

        $this->assertFalse($this->policy->update($user, $paymentMethod));
    }

    public function test_update_denies_owner_of_different_outlet(): void
    {
        $user = User::factory()->create();
        $userOutlet = \Database\Factories\OutletFactory::new()->create();
        $otherOutlet = \Database\Factories\OutletFactory::new()->create();
        $paymentMethod = PaymentMethod::factory()->create(['outlet_id' => $otherOutlet->id]);

        // User is owner of a different outlet
        UserOutlet::factory()->create([
            'user_id' => $user->id,
            'outlet_id' => $userOutlet->id,
            'role' => UserOutlet::ROLE_OWNER,
            'is_active' => true,
        ]);

        $this->assertFalse($this->policy->update($user, $paymentMethod));
    }

    public function test_update_denies_non_member(): void
    {
        $user = User::factory()->create();
        $outlet = \Database\Factories\OutletFactory::new()->create();
        $paymentMethod = PaymentMethod::factory()->create(['outlet_id' => $outlet->id]);

        $this->assertFalse($this->policy->update($user, $paymentMethod));
    }

    public function test_delete_allows_owner_of_payment_method_outlet(): void
    {
        $user = User::factory()->create();
        $outlet = \Database\Factories\OutletFactory::new()->create();
        $paymentMethod = PaymentMethod::factory()->create(['outlet_id' => $outlet->id]);

        UserOutlet::factory()->create([
            'user_id' => $user->id,
            'outlet_id' => $outlet->id,
            'role' => UserOutlet::ROLE_OWNER,
            'is_active' => true,
        ]);

        $this->assertTrue($this->policy->delete($user, $paymentMethod));
    }

    public function test_delete_denies_karyawan_of_payment_method_outlet(): void
    {
        $user = User::factory()->create();
        $outlet = \Database\Factories\OutletFactory::new()->create();
        $paymentMethod = PaymentMethod::factory()->create(['outlet_id' => $outlet->id]);

        UserOutlet::factory()->create([
            'user_id' => $user->id,
            'outlet_id' => $outlet->id,
            'role' => UserOutlet::ROLE_KARYAWAN,
            'is_active' => true,
        ]);

        $this->assertFalse($this->policy->delete($user, $paymentMethod));
    }

    public function test_delete_denies_owner_of_different_outlet(): void
    {
        $user = User::factory()->create();
        $userOutlet = \Database\Factories\OutletFactory::new()->create();
        $otherOutlet = \Database\Factories\OutletFactory::new()->create();
        $paymentMethod = PaymentMethod::factory()->create(['outlet_id' => $otherOutlet->id]);

        UserOutlet::factory()->create([
            'user_id' => $user->id,
            'outlet_id' => $userOutlet->id,
            'role' => UserOutlet::ROLE_OWNER,
            'is_active' => true,
        ]);

        $this->assertFalse($this->policy->delete($user, $paymentMethod));
    }

    public function test_delete_denies_non_member(): void
    {
        $user = User::factory()->create();
        $outlet = \Database\Factories\OutletFactory::new()->create();
        $paymentMethod = PaymentMethod::factory()->create(['outlet_id' => $outlet->id]);

        $this->assertFalse($this->policy->delete($user, $paymentMethod));
    }

    public function test_payment_method_with_relationship_loaded(): void
    {
        $user = User::factory()->create();
        $outlet = \Database\Factories\OutletFactory::new()->create();
        $paymentMethod = PaymentMethod::factory()->create(['outlet_id' => $outlet->id]);

        UserOutlet::factory()->create([
            'user_id' => $user->id,
            'outlet_id' => $outlet->id,
            'role' => UserOutlet::ROLE_OWNER,
            'is_active' => true,
        ]);

        // Load the outlet relationship
        $paymentMethod->load('outlet');

        $this->assertTrue($this->policy->update($user, $paymentMethod));
        $this->assertTrue($this->policy->delete($user, $paymentMethod));
    }
}
