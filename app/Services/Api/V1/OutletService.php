<?php

namespace App\Services\Api\V1;

use App\Domain\Permissions\OutletPermissions;
use App\Models\Outlet;
use App\Models\User;
use App\Models\UserOutlet;
use Exception;

class OutletService
{
    /**
     * Create outlet for owner and assign owner role.
     *
     * @throws Exception
     */
    public function createForOwner(User $owner, array $data): Outlet
    {
        if (! $owner->is_active) {
            throw new Exception('User must be active to create outlet');
        }

        $data['owner_user_id'] = $owner->id;
        $data['is_active'] = $data['is_active'] ?? true;

        $outlet = Outlet::create($data);

        if (! $outlet) {
            throw new Exception('Failed to create outlet');
        }

        // Attach owner to outlet with owner permissions
        $this->assignUser($outlet, $owner, UserOutlet::ROLE_OWNER);

        return $outlet->load(['owner', 'userOutlets']);
    }

    /**
     * Assign user to outlet with specified role and permissions.
     *
     * @throws Exception
     */
    public function assignUser(Outlet $outlet, User $user, string $role, ?array $permOverride = null): UserOutlet
    {
        if (! $outlet->is_active) {
            throw new Exception('Cannot assign user to inactive outlet');
        }

        if (! $user->is_active) {
            throw new Exception('Cannot assign inactive user to outlet');
        }

        if (! in_array($role, [UserOutlet::ROLE_OWNER, UserOutlet::ROLE_KARYAWAN], true)) {
            throw new Exception('Invalid role specified');
        }

        // Get base permissions for role
        $basePermissions = OutletPermissions::defaultsFor($role);

        // Merge with override permissions
        $finalPermissions = OutletPermissions::merge($basePermissions, $permOverride ?? []);

        // Upsert user outlet relationship
        $userOutlet = UserOutlet::updateOrCreate(
            [
                'user_id' => $user->id,
                'outlet_id' => $outlet->id,
            ],
            [
                'role' => $role,
                'permissions_json' => $finalPermissions,
                'is_active' => true,
            ]
        );

        if (! $userOutlet) {
            throw new Exception('Failed to assign user to outlet');
        }

        return $userOutlet->load(['user', 'outlet']);
    }
}
