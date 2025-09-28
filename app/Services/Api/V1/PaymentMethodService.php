<?php

namespace App\Services\Api\V1;

use App\Models\Outlet;
use App\Models\PaymentMethod;
use Exception;

class PaymentMethodService
{
    /**
     * Create payment method for outlet.
     *
     * @throws Exception
     */
    public function create(Outlet $outlet, array $data): PaymentMethod
    {
        if (! $outlet->is_active) {
            throw new Exception('Cannot create payment method for inactive outlet');
        }

        // Validate category
        $validCategories = [
            PaymentMethod::CATEGORY_CASH,
            PaymentMethod::CATEGORY_TRANSFER,
            PaymentMethod::CATEGORY_E_WALLET,
        ];

        if (! in_array($data['category'], $validCategories, true)) {
            throw new Exception('Invalid payment method category');
        }

        $data['outlet_id'] = $outlet->id;
        $data['is_active'] = $data['is_active'] ?? true;

        // Ensure tags is an array
        if (isset($data['tags']) && ! is_array($data['tags'])) {
            $data['tags'] = [];
        }

        $paymentMethod = PaymentMethod::create($data);

        if (! $paymentMethod) {
            throw new Exception('Failed to create payment method');
        }

        return $paymentMethod->load('outlet');
    }

    /**
     * Update payment method.
     *
     * @throws Exception
     */
    public function update(PaymentMethod $paymentMethod, array $data): PaymentMethod
    {
        // Validate category if provided
        if (isset($data['category'])) {
            $validCategories = [
                PaymentMethod::CATEGORY_CASH,
                PaymentMethod::CATEGORY_TRANSFER,
                PaymentMethod::CATEGORY_E_WALLET,
            ];

            if (! in_array($data['category'], $validCategories, true)) {
                throw new Exception('Invalid payment method category');
            }
        }

        // Ensure tags is an array if provided
        if (isset($data['tags']) && ! is_array($data['tags'])) {
            $data['tags'] = [];
        }

        $updated = $paymentMethod->update($data);

        if (! $updated) {
            throw new Exception('Failed to update payment method');
        }

        return $paymentMethod->fresh(['outlet']);
    }

    /**
     * Delete payment method (soft delete by setting is_active to false).
     *
     * @throws Exception
     */
    public function delete(PaymentMethod $paymentMethod): void
    {
        $updated = $paymentMethod->update(['is_active' => false]);

        if (! $updated) {
            throw new Exception('Failed to delete payment method');
        }
    }
}
