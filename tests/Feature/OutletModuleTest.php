<?php

namespace Tests\Feature;

use App\Domain\Permissions\OutletPermissions;
use App\Models\Outlet;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Models\UserOutlet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OutletModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_outlet_with_owner(): void
    {
        $owner = User::factory()->create();
        $outlet = \Database\Factories\OutletFactory::new()->create(['owner_user_id' => $owner->id]);

        UserOutlet::factory()->create([
            'user_id' => $owner->id,
            'outlet_id' => $outlet->id,
            'role' => UserOutlet::ROLE_OWNER,
        ]);

        $this->assertDatabaseHas('outlets', [
            'id' => $outlet->id,
            'owner_user_id' => $owner->id,
        ]);

        $this->assertDatabaseHas('user_outlets', [
            'user_id' => $owner->id,
            'outlet_id' => $outlet->id,
            'role' => UserOutlet::ROLE_OWNER,
        ]);
    }

    public function test_outlet_relationships_work(): void
    {
        $owner = User::factory()->create();
        $outlet = \Database\Factories\OutletFactory::new()->create(['owner_user_id' => $owner->id]);

        UserOutlet::factory()->create([
            'user_id' => $owner->id,
            'outlet_id' => $outlet->id,
            'role' => UserOutlet::ROLE_OWNER,
        ]);

        // Test outlet belongs to owner
        $this->assertEquals($owner->id, $outlet->owner->id);

        // Test user has outlets
        $this->assertCount(1, $owner->outlets);
        $this->assertEquals($outlet->id, $owner->outlets->first()->id);

        // Test pivot data
        $this->assertEquals(UserOutlet::ROLE_OWNER, $owner->outlets->first()->pivot->role);
    }

    public function test_can_create_payment_methods_for_outlet(): void
    {
        $outlet = \Database\Factories\OutletFactory::new()->create();

        $cashPayment = PaymentMethod::factory()->cash()->create(['outlet_id' => $outlet->id]);
        $transferPayment = PaymentMethod::factory()->transfer()->create(['outlet_id' => $outlet->id]);
        $eWalletPayment = PaymentMethod::factory()->eWallet()->create(['outlet_id' => $outlet->id]);

        $this->assertCount(3, $outlet->paymentMethods);

        $this->assertEquals(PaymentMethod::CATEGORY_CASH, $cashPayment->category);
        $this->assertEquals(PaymentMethod::CATEGORY_TRANSFER, $transferPayment->category);
        $this->assertEquals(PaymentMethod::CATEGORY_E_WALLET, $eWalletPayment->category);

        // Test payment method belongs to outlet
        $this->assertEquals($outlet->id, $cashPayment->outlet->id);
    }

    public function test_user_outlet_role_constants(): void
    {
        $this->assertEquals('owner', UserOutlet::ROLE_OWNER);
        $this->assertEquals('karyawan', UserOutlet::ROLE_KARYAWAN);
    }

    public function test_payment_method_category_constants(): void
    {
        $this->assertEquals('cash', PaymentMethod::CATEGORY_CASH);
        $this->assertEquals('transfer', PaymentMethod::CATEGORY_TRANSFER);
        $this->assertEquals('e_wallet', PaymentMethod::CATEGORY_E_WALLET);
    }

    public function test_json_casts_work_properly(): void
    {
        $userOutlet = UserOutlet::factory()->create([
            'permissions_json' => ['read', 'write', 'delete'],
        ]);

        $paymentMethod = PaymentMethod::factory()->create([
            'tags' => ['populer', 'cepat', 'mudah'],
        ]);

        // Test array casting
        $this->assertIsArray($userOutlet->permissions_json);
        $this->assertEquals(['read', 'write', 'delete'], $userOutlet->permissions_json);

        $this->assertIsArray($paymentMethod->tags);
        $this->assertEquals(['populer', 'cepat', 'mudah'], $paymentMethod->tags);
    }

    public function test_user_outlet_permissions_integration(): void
    {
        // Test owner permissions
        $owner = UserOutlet::factory()->owner()->create();
        $ownerPermissions = $owner->getEffectivePermissions();

        // Owner should have all permissions
        $this->assertTrue($owner->hasPermission(OutletPermissions::CREATE_ORDER));
        $this->assertTrue($owner->hasPermission(OutletPermissions::MANAGE_EMPLOYEES));
        $this->assertTrue($owner->hasPermission(OutletPermissions::VIEW_REVENUE));

        // Test karyawan permissions
        $karyawan = UserOutlet::factory()->karyawan()->create();

        // Karyawan should have some permissions
        $this->assertTrue($karyawan->hasPermission(OutletPermissions::CREATE_ORDER));
        $this->assertTrue($karyawan->hasPermission(OutletPermissions::MANAGE_CUSTOMERS));

        // But not others
        $this->assertFalse($karyawan->hasPermission(OutletPermissions::MANAGE_EMPLOYEES));
        $this->assertFalse($karyawan->hasPermission(OutletPermissions::VIEW_REVENUE));

        // Test permission override
        $karyawanWithOverride = UserOutlet::factory()->karyawan()->create([
            'permissions_json' => [
                OutletPermissions::VIEW_REVENUE => true,
                OutletPermissions::MANAGE_CUSTOMERS => false,
            ],
        ]);

        $this->assertTrue($karyawanWithOverride->hasPermission(OutletPermissions::VIEW_REVENUE)); // Overridden to true
        $this->assertFalse($karyawanWithOverride->hasPermission(OutletPermissions::MANAGE_CUSTOMERS)); // Overridden to false
        $this->assertTrue($karyawanWithOverride->hasPermission(OutletPermissions::CREATE_ORDER)); // Default karyawan permission
    }
}
