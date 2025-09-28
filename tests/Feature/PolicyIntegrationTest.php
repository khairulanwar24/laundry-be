<?php

namespace Tests\Feature;

use App\Models\Outlet;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Models\UserOutlet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class PolicyIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_gate_uses_outlet_policy(): void
    {
        $owner = User::factory()->create();
        $karyawan = User::factory()->create();
        $outsider = User::factory()->create();
        $outlet = \Database\Factories\OutletFactory::new()->create();

        // Create relationships
        UserOutlet::factory()->create([
            'user_id' => $owner->id,
            'outlet_id' => $outlet->id,
            'role' => UserOutlet::ROLE_OWNER,
            'is_active' => true,
        ]);

        UserOutlet::factory()->create([
            'user_id' => $karyawan->id,
            'outlet_id' => $outlet->id,
            'role' => UserOutlet::ROLE_KARYAWAN,
            'is_active' => true,
        ]);

        // Test view permission
        $this->assertTrue(Gate::forUser($owner)->allows('view', $outlet));
        $this->assertTrue(Gate::forUser($karyawan)->allows('view', $outlet));
        $this->assertFalse(Gate::forUser($outsider)->allows('view', $outlet));

        // Test update permission
        $this->assertTrue(Gate::forUser($owner)->allows('update', $outlet));
        $this->assertFalse(Gate::forUser($karyawan)->allows('update', $outlet));
        $this->assertFalse(Gate::forUser($outsider)->allows('update', $outlet));

        // Test invite permission
        $this->assertTrue(Gate::forUser($owner)->allows('invite', $outlet));
        $this->assertFalse(Gate::forUser($karyawan)->allows('invite', $outlet));
        $this->assertFalse(Gate::forUser($outsider)->allows('invite', $outlet));
    }

    public function test_gate_uses_payment_method_policy(): void
    {
        $owner = User::factory()->create();
        $karyawan = User::factory()->create();
        $outsider = User::factory()->create();
        $outlet = \Database\Factories\OutletFactory::new()->create();
        $paymentMethod = PaymentMethod::factory()->create(['outlet_id' => $outlet->id]);

        // Create relationships
        UserOutlet::factory()->create([
            'user_id' => $owner->id,
            'outlet_id' => $outlet->id,
            'role' => UserOutlet::ROLE_OWNER,
            'is_active' => true,
        ]);

        UserOutlet::factory()->create([
            'user_id' => $karyawan->id,
            'outlet_id' => $outlet->id,
            'role' => UserOutlet::ROLE_KARYAWAN,
            'is_active' => true,
        ]);

        // Test viewAny permission (requires outlet parameter)
        $this->assertTrue(Gate::forUser($owner)->check('viewAny', [PaymentMethod::class, $outlet]));
        $this->assertTrue(Gate::forUser($karyawan)->check('viewAny', [PaymentMethod::class, $outlet]));
        $this->assertFalse(Gate::forUser($outsider)->check('viewAny', [PaymentMethod::class, $outlet]));

        // Test create permission (requires outlet parameter)
        $this->assertTrue(Gate::forUser($owner)->check('create', [PaymentMethod::class, $outlet]));
        $this->assertFalse(Gate::forUser($karyawan)->check('create', [PaymentMethod::class, $outlet]));
        $this->assertFalse(Gate::forUser($outsider)->check('create', [PaymentMethod::class, $outlet]));

        // Test update permission
        $this->assertTrue(Gate::forUser($owner)->allows('update', $paymentMethod));
        $this->assertFalse(Gate::forUser($karyawan)->allows('update', $paymentMethod));
        $this->assertFalse(Gate::forUser($outsider)->allows('update', $paymentMethod));

        // Test delete permission
        $this->assertTrue(Gate::forUser($owner)->allows('delete', $paymentMethod));
        $this->assertFalse(Gate::forUser($karyawan)->allows('delete', $paymentMethod));
        $this->assertFalse(Gate::forUser($outsider)->allows('delete', $paymentMethod));
    }

    public function test_authorize_helper_with_policies(): void
    {
        $this->actingAs($user = User::factory()->create());
        $outlet = \Database\Factories\OutletFactory::new()->create();

        // User is not a member, should get 403
        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);

        Gate::authorize('view', $outlet);
    }

    public function test_authorize_helper_passes_for_authorized_user(): void
    {
        $this->actingAs($user = User::factory()->create());
        $outlet = \Database\Factories\OutletFactory::new()->create();

        UserOutlet::factory()->create([
            'user_id' => $user->id,
            'outlet_id' => $outlet->id,
            'role' => UserOutlet::ROLE_OWNER,
            'is_active' => true,
        ]);

        // Should not throw exception
        Gate::authorize('view', $outlet);
        Gate::authorize('update', $outlet);
        Gate::authorize('invite', $outlet);

        $this->assertTrue(true); // If we reach here, authorization passed
    }

    public function test_policy_with_different_outlets(): void
    {
        $user = User::factory()->create();
        $outlet1 = \Database\Factories\OutletFactory::new()->create();
        $outlet2 = \Database\Factories\OutletFactory::new()->create();
        $paymentMethod1 = PaymentMethod::factory()->create(['outlet_id' => $outlet1->id]);
        $paymentMethod2 = PaymentMethod::factory()->create(['outlet_id' => $outlet2->id]);

        // User is owner of outlet1 only
        UserOutlet::factory()->create([
            'user_id' => $user->id,
            'outlet_id' => $outlet1->id,
            'role' => UserOutlet::ROLE_OWNER,
            'is_active' => true,
        ]);

        // Should have access to outlet1 and its payment methods
        $this->assertTrue(Gate::forUser($user)->allows('view', $outlet1));
        $this->assertTrue(Gate::forUser($user)->allows('update', $outlet1));
        $this->assertTrue(Gate::forUser($user)->allows('update', $paymentMethod1));
        $this->assertTrue(Gate::forUser($user)->allows('delete', $paymentMethod1));

        // Should NOT have access to outlet2 and its payment methods
        $this->assertFalse(Gate::forUser($user)->allows('view', $outlet2));
        $this->assertFalse(Gate::forUser($user)->allows('update', $outlet2));
        $this->assertFalse(Gate::forUser($user)->allows('update', $paymentMethod2));
        $this->assertFalse(Gate::forUser($user)->allows('delete', $paymentMethod2));
    }
}
