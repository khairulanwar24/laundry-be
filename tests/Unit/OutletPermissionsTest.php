<?php

namespace Tests\Unit;

use App\Domain\Permissions\OutletPermissions;
use PHPUnit\Framework\TestCase;

class OutletPermissionsTest extends TestCase
{
    public function test_all_returns_all_permissions_with_false_defaults(): void
    {
        $permissions = OutletPermissions::all();

        $expectedKeys = [
            OutletPermissions::CREATE_ORDER,
            OutletPermissions::CANCEL_ORDER,
            OutletPermissions::CREATE_EXPENSE,
            OutletPermissions::MANAGE_SERVICES,
            OutletPermissions::MANAGE_CUSTOMERS,
            OutletPermissions::MANAGE_EMPLOYEES,
            OutletPermissions::VIEW_REVENUE,
            OutletPermissions::VIEW_REPORT_TX,
            OutletPermissions::VIEW_REPORT_FINANCE,
            OutletPermissions::VIEW_REPORT_CUSTOMER,
        ];

        // Check all expected keys exist
        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $permissions);
            $this->assertFalse($permissions[$key]);
        }

        $this->assertCount(10, $permissions);
    }

    public function test_defaults_for_owner_has_all_permissions_true(): void
    {
        $permissions = OutletPermissions::defaultsFor('owner');

        foreach ($permissions as $permission => $value) {
            $this->assertTrue($value, "Permission '{$permission}' should be true for owner");
        }

        $this->assertCount(10, $permissions);
    }

    public function test_defaults_for_karyawan_has_correct_permissions(): void
    {
        $permissions = OutletPermissions::defaultsFor('karyawan');

        // Should be true
        $this->assertTrue($permissions[OutletPermissions::CREATE_ORDER]);
        $this->assertTrue($permissions[OutletPermissions::CANCEL_ORDER]);
        $this->assertTrue($permissions[OutletPermissions::CREATE_EXPENSE]);
        $this->assertTrue($permissions[OutletPermissions::MANAGE_CUSTOMERS]);

        // Should be false
        $this->assertFalse($permissions[OutletPermissions::MANAGE_SERVICES]);
        $this->assertFalse($permissions[OutletPermissions::MANAGE_EMPLOYEES]);
        $this->assertFalse($permissions[OutletPermissions::VIEW_REVENUE]);
        $this->assertFalse($permissions[OutletPermissions::VIEW_REPORT_TX]);
        $this->assertFalse($permissions[OutletPermissions::VIEW_REPORT_FINANCE]);
        $this->assertFalse($permissions[OutletPermissions::VIEW_REPORT_CUSTOMER]);

        $this->assertCount(10, $permissions);
    }

    public function test_defaults_for_unknown_role_returns_all_false(): void
    {
        $permissions = OutletPermissions::defaultsFor('unknown_role');

        foreach ($permissions as $permission => $value) {
            $this->assertFalse($value, "Permission '{$permission}' should be false for unknown role");
        }
    }

    public function test_merge_overrides_only_recognized_permissions(): void
    {
        $base = [
            OutletPermissions::CREATE_ORDER => false,
            OutletPermissions::MANAGE_SERVICES => false,
            OutletPermissions::VIEW_REVENUE => false,
        ];

        $override = [
            OutletPermissions::CREATE_ORDER => true,
            OutletPermissions::VIEW_REVENUE => true,
            'unknown_permission' => true, // Should be ignored
            OutletPermissions::MANAGE_SERVICES => 'not_boolean', // Should be ignored
        ];

        $result = OutletPermissions::merge($base, $override);

        $this->assertTrue($result[OutletPermissions::CREATE_ORDER]);
        $this->assertTrue($result[OutletPermissions::VIEW_REVENUE]);
        $this->assertFalse($result[OutletPermissions::MANAGE_SERVICES]); // Not overridden due to non-boolean value
        $this->assertArrayNotHasKey('unknown_permission', $result);
    }

    public function test_merge_preserves_base_permissions_not_in_override(): void
    {
        $base = [
            OutletPermissions::CREATE_ORDER => true,
            OutletPermissions::MANAGE_SERVICES => true,
            OutletPermissions::VIEW_REVENUE => false,
        ];

        $override = [
            OutletPermissions::CREATE_ORDER => false,
        ];

        $result = OutletPermissions::merge($base, $override);

        $this->assertFalse($result[OutletPermissions::CREATE_ORDER]);
        $this->assertTrue($result[OutletPermissions::MANAGE_SERVICES]); // Preserved from base
        $this->assertFalse($result[OutletPermissions::VIEW_REVENUE]); // Preserved from base
    }

    public function test_permission_constants_have_correct_values(): void
    {
        $this->assertEquals('create_order', OutletPermissions::CREATE_ORDER);
        $this->assertEquals('cancel_order', OutletPermissions::CANCEL_ORDER);
        $this->assertEquals('create_expense', OutletPermissions::CREATE_EXPENSE);
        $this->assertEquals('manage_services', OutletPermissions::MANAGE_SERVICES);
        $this->assertEquals('manage_customers', OutletPermissions::MANAGE_CUSTOMERS);
        $this->assertEquals('manage_employees', OutletPermissions::MANAGE_EMPLOYEES);
        $this->assertEquals('view_revenue', OutletPermissions::VIEW_REVENUE);
        $this->assertEquals('view_report_tx', OutletPermissions::VIEW_REPORT_TX);
        $this->assertEquals('view_report_finance', OutletPermissions::VIEW_REPORT_FINANCE);
        $this->assertEquals('view_report_customer', OutletPermissions::VIEW_REPORT_CUSTOMER);
    }
}
