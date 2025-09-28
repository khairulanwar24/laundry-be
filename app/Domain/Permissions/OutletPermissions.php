<?php

namespace App\Domain\Permissions;

class OutletPermissions
{
    public const CREATE_ORDER = 'create_order';

    public const CANCEL_ORDER = 'cancel_order';

    public const CREATE_EXPENSE = 'create_expense';

    public const MANAGE_SERVICES = 'manage_services';

    public const MANAGE_CUSTOMERS = 'manage_customers';

    public const MANAGE_EMPLOYEES = 'manage_employees';

    public const VIEW_REVENUE = 'view_revenue';

    public const VIEW_REPORT_TX = 'view_report_tx';

    public const VIEW_REPORT_FINANCE = 'view_report_finance';

    public const VIEW_REPORT_CUSTOMER = 'view_report_customer';

    /**
     * Get all permissions with default false values.
     */
    public static function all(): array
    {
        return [
            self::CREATE_ORDER => false,
            self::CANCEL_ORDER => false,
            self::CREATE_EXPENSE => false,
            self::MANAGE_SERVICES => false,
            self::MANAGE_CUSTOMERS => false,
            self::MANAGE_EMPLOYEES => false,
            self::VIEW_REVENUE => false,
            self::VIEW_REPORT_TX => false,
            self::VIEW_REPORT_FINANCE => false,
            self::VIEW_REPORT_CUSTOMER => false,
        ];
    }

    /**
     * Get default permissions for a specific role.
     */
    public static function defaultsFor(string $role): array
    {
        return match ($role) {
            'owner' => [
                self::CREATE_ORDER => true,
                self::CANCEL_ORDER => true,
                self::CREATE_EXPENSE => true,
                self::MANAGE_SERVICES => true,
                self::MANAGE_CUSTOMERS => true,
                self::MANAGE_EMPLOYEES => true,
                self::VIEW_REVENUE => true,
                self::VIEW_REPORT_TX => true,
                self::VIEW_REPORT_FINANCE => true,
                self::VIEW_REPORT_CUSTOMER => true,
            ],
            'karyawan' => [
                self::CREATE_ORDER => true,
                self::CANCEL_ORDER => true,
                self::CREATE_EXPENSE => true,
                self::MANAGE_SERVICES => false,
                self::MANAGE_CUSTOMERS => true,
                self::MANAGE_EMPLOYEES => false,
                self::VIEW_REVENUE => false,
                self::VIEW_REPORT_TX => false,
                self::VIEW_REPORT_FINANCE => false,
                self::VIEW_REPORT_CUSTOMER => false,
            ],
            default => self::all(),
        };
    }

    /**
     * Merge override permissions into base permissions.
     * Only recognized permission keys will be merged.
     */
    public static function merge(array $base, array $override): array
    {
        $recognizedKeys = array_keys(self::all());
        $merged = $base;

        foreach ($override as $key => $value) {
            if (in_array($key, $recognizedKeys, true) && is_bool($value)) {
                $merged[$key] = $value;
            }
        }

        return $merged;
    }
}
