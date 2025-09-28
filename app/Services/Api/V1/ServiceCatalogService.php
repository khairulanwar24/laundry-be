<?php

namespace App\Services\Api\V1;

use App\Models\Discount;
use App\Models\Outlet;
use App\Models\Perfume;
use App\Models\Service;
use App\Models\ServiceVariant;
use Exception;
use Illuminate\Database\Eloquent\Collection;

class ServiceCatalogService
{
    /**
     * List services with variants for an outlet, with optional search query.
     */
    public function listServices(Outlet $outlet, ?string $q = null): Collection
    {
        $query = Service::where('outlet_id', $outlet->id)
            ->with(['serviceVariants' => function ($query) {
                $query->where('is_active', true)->orderBy('name');
            }])
            ->where('is_active', true);

        if ($q) {
            $query->where(function ($subQuery) use ($q) {
                $subQuery->where('name', 'like', "%{$q}%")
                    ->orWhereHas('serviceVariants', function ($variantQuery) use ($q) {
                        $variantQuery->where('name', 'like', "%{$q}%")
                            ->where('is_active', true);
                    });
            });
        }

        return $query->orderBy('priority_score', 'desc')
            ->orderBy('name')
            ->get();
    }

    /**
     * Create a new service for an outlet.
     *
     * @throws Exception
     */
    public function createService(Outlet $outlet, array $data): Service
    {
        if (! $outlet->is_active) {
            throw new Exception('Cannot create service for inactive outlet');
        }

        // Validate process steps if provided
        if (isset($data['process_steps_json'])) {
            $this->validateProcessSteps($data['process_steps_json']);
        }

        $data['outlet_id'] = $outlet->id;
        $data['is_active'] = $data['is_active'] ?? true;

        // Set default process steps if not provided
        if (! isset($data['process_steps_json'])) {
            $data['process_steps_json'] = ['cuci', 'kering', 'setrika'];
        }

        // Set default priority score if not provided
        if (! isset($data['priority_score'])) {
            $data['priority_score'] = 50;
        }

        $service = Service::create($data);

        if (! $service) {
            throw new Exception('Failed to create service');
        }

        return $service->load('outlet');
    }

    /**
     * Update an existing service.
     *
     * @throws Exception
     */
    public function updateService(Service $service, array $data): Service
    {
        // Validate process steps if provided
        if (isset($data['process_steps_json'])) {
            $this->validateProcessSteps($data['process_steps_json']);
        }

        $updated = $service->update($data);

        if (! $updated) {
            throw new Exception('Failed to update service');
        }

        return $service->fresh(['outlet']);
    }

    /**
     * Create a new service variant.
     *
     * @throws Exception
     */
    public function createVariant(Service $service, array $data): ServiceVariant
    {
        if (! $service->is_active) {
            throw new Exception('Cannot create variant for inactive service');
        }

        // Check if the outlet is active by querying directly
        $outlet = Outlet::find($service->outlet_id);
        if (! $outlet || ! $outlet->is_active) {
            throw new Exception('Cannot create variant for service in inactive outlet');
        }

        $data['service_id'] = $service->id;
        $data['is_active'] = $data['is_active'] ?? true;

        $variant = ServiceVariant::create($data);

        if (! $variant) {
            throw new Exception('Failed to create service variant');
        }

        return $variant->load('service');
    }

    /**
     * Update an existing service variant.
     *
     * @throws Exception
     */
    public function updateVariant(ServiceVariant $variant, array $data): ServiceVariant
    {
        $updated = $variant->update($data);

        if (! $updated) {
            throw new Exception('Failed to update service variant');
        }

        return $variant->fresh(['service']);
    }

    /**
     * Create a new perfume for an outlet.
     *
     * @throws Exception
     */
    public function createPerfume(Outlet $outlet, array $data): Perfume
    {
        if (! $outlet->is_active) {
            throw new Exception('Cannot create perfume for inactive outlet');
        }

        $data['outlet_id'] = $outlet->id;
        $data['is_active'] = $data['is_active'] ?? true;

        $perfume = Perfume::create($data);

        if (! $perfume) {
            throw new Exception('Failed to create perfume');
        }

        return $perfume->load('outlet');
    }

    /**
     * Update an existing perfume.
     *
     * @throws Exception
     */
    public function updatePerfume(Perfume $perfume, array $data): Perfume
    {
        $updated = $perfume->update($data);

        if (! $updated) {
            throw new Exception('Failed to update perfume');
        }

        return $perfume->fresh(['outlet']);
    }

    /**
     * Create a new discount for an outlet.
     *
     * @throws Exception
     */
    public function createDiscount(Outlet $outlet, array $data): Discount
    {
        if (! $outlet->is_active) {
            throw new Exception('Cannot create discount for inactive outlet');
        }

        $data['outlet_id'] = $outlet->id;
        $data['is_active'] = $data['is_active'] ?? true;

        $discount = Discount::create($data);

        if (! $discount) {
            throw new Exception('Failed to create discount');
        }

        return $discount->load('outlet');
    }

    /**
     * Update an existing discount.
     *
     * @throws Exception
     */
    public function updateDiscount(Discount $discount, array $data): Discount
    {
        $updated = $discount->update($data);

        if (! $updated) {
            throw new Exception('Failed to update discount');
        }

        return $discount->fresh(['outlet']);
    }

    /**
     * Validate process steps array.
     *
     * @throws Exception
     */
    private function validateProcessSteps(array $steps): void
    {
        $allowedSteps = ['cuci', 'kering', 'setrika'];

        foreach ($steps as $step) {
            if (! in_array($step, $allowedSteps, true)) {
                throw new Exception("Invalid process step: {$step}. Allowed steps are: ".implode(', ', $allowedSteps));
            }
        }

        if (empty($steps)) {
            throw new Exception('Process steps cannot be empty');
        }
    }
}
