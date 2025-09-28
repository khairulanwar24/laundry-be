<?php

namespace App\Policies;

use App\Models\Outlet;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Models\UserOutlet;

class PaymentMethodPolicy
{
    /**
     * Determine whether the user can view any payment methods for an outlet.
     * User must be a member of the outlet.
     */
    public function viewAny(User $user, Outlet $outlet): bool
    {
        return $this->isUserMemberOfOutlet($user, $outlet);
    }

    /**
     * Determine whether the user can create payment methods for an outlet.
     * User must be an owner of the outlet.
     */
    public function create(User $user, Outlet $outlet): bool
    {
        return $this->isUserOwnerOfOutlet($user, $outlet);
    }

    /**
     * Determine whether the user can update the payment method.
     * User must be an owner of the outlet that owns the payment method.
     */
    public function update(User $user, PaymentMethod $paymentMethod): bool
    {
        return $this->isUserOwnerOfOutlet($user, $paymentMethod->outlet);
    }

    /**
     * Determine whether the user can delete the payment method.
     * User must be an owner of the outlet that owns the payment method.
     */
    public function delete(User $user, PaymentMethod $paymentMethod): bool
    {
        return $this->isUserOwnerOfOutlet($user, $paymentMethod->outlet);
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
