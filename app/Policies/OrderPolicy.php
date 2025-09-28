<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\Outlet;
use App\Models\User;
use App\Models\UserOutlet;

class OrderPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user, Outlet $outlet): bool
    {
        return $this->userIsMemberOfOutlet($user, $outlet);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Order $order, Outlet $outlet): bool
    {
        return $this->userIsMemberOfOutlet($user, $outlet) && $order->outlet_id === $outlet->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, Outlet $outlet): bool
    {
        return $this->userIsMemberOfOutlet($user, $outlet);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Order $order, Outlet $outlet): bool
    {
        return $this->userIsMemberOfOutlet($user, $outlet) && $order->outlet_id === $outlet->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Order $order, Outlet $outlet): bool
    {
        return $this->userIsMemberOfOutlet($user, $outlet) && $order->outlet_id === $outlet->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Order $order, Outlet $outlet): bool
    {
        return $this->userIsMemberOfOutlet($user, $outlet) && $order->outlet_id === $outlet->id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Order $order, Outlet $outlet): bool
    {
        return $this->userIsMemberOfOutlet($user, $outlet) && $order->outlet_id === $outlet->id;
    }

    /**
     * Check if user is a member of the outlet.
     */
    private function userIsMemberOfOutlet(User $user, Outlet $outlet): bool
    {
        return UserOutlet::where('user_id', $user->id)
            ->where('outlet_id', $outlet->id)
            ->exists();
    }
}
