<?php

namespace App\Policies;

use App\Models\Outlet;
use App\Models\User;
use App\Models\UserOutlet;

class OutletPolicy
{
    /**
     * Determine whether the user can view the outlet.
     * User must be a member of the outlet.
     */
    public function view(User $user, Outlet $outlet): bool
    {
        return $this->isUserMemberOfOutlet($user, $outlet);
    }

    /**
     * Determine whether the user can update the outlet.
     * User must be an owner of the outlet.
     */
    public function update(User $user, Outlet $outlet): bool
    {
        return $this->isUserOwnerOfOutlet($user, $outlet);
    }

    /**
     * Determine whether the user can invite others to the outlet.
     * User must be an owner of the outlet.
     */
    public function invite(User $user, Outlet $outlet): bool
    {
        return $this->isUserOwnerOfOutlet($user, $outlet);
    }

    /**
     * Determine whether the user can create outlets.
     * Any active user can create outlets.
     */
    public function create(User $user): bool
    {
        return $user->is_active;
    }

    /**
     * Determine whether the user is a member of the outlet.
     * User must be an active member of the outlet.
     */
    public function member(User $user, Outlet $outlet): bool
    {
        return $this->isUserMemberOfOutlet($user, $outlet);
    }

    /**
     * Check if user is a member of the outlet (has any role).
     */
    private function isUserMemberOfOutlet(User $user, Outlet $outlet): bool
    {
        return UserOutlet::where('user_id', $user->id)
            ->where('outlet_id', $outlet->id)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Check if user is an owner of the outlet.
     */
    private function isUserOwnerOfOutlet(User $user, Outlet $outlet): bool
    {
        return UserOutlet::where('user_id', $user->id)
            ->where('outlet_id', $outlet->id)
            ->where('role', UserOutlet::ROLE_OWNER)
            ->where('is_active', true)
            ->exists();
    }
}
