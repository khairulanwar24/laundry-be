<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Discount\StoreDiscountRequest;
use App\Http\Requests\Api\V1\Discount\UpdateDiscountRequest;
use App\Http\Resources\Api\V1\DiscountResource;
use App\Models\Discount;
use App\Models\Outlet;
use App\Services\Api\V1\ServiceCatalogService;
use App\Support\ResponseJson;
use Exception;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

class DiscountController extends Controller
{
    use AuthorizesRequests, ResponseJson;

    public function __construct(
        private ServiceCatalogService $serviceCatalogService
    ) {}

    /**
     * Display a listing of active discounts for the outlet.
     */
    public function index(Outlet $outlet): JsonResponse
    {
        $this->authorize('member', $outlet);

        $discounts = Discount::where('outlet_id', $outlet->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return $this->ok(DiscountResource::collection($discounts));
    }

    /**
     * Store a newly created discount.
     */
    public function store(StoreDiscountRequest $request, Outlet $outlet): JsonResponse
    {
        try {
            $this->authorize('member', $outlet);

            $discount = $this->serviceCatalogService->createDiscount(
                $outlet,
                $request->validated()
            );

            return $this->created(new DiscountResource($discount), 'Diskon berhasil dibuat');
        } catch (Exception $e) {
            return $this->badRequest('Gagal membuat diskon: '.$e->getMessage());
        }
    }

    /**
     * Update the specified discount.
     */
    public function update(
        UpdateDiscountRequest $request,
        Outlet $outlet,
        Discount $discount
    ): JsonResponse {
        try {
            // Ensure discount belongs to the outlet - return 404 if not
            if ($discount->outlet_id !== $outlet->id) {
                return $this->notFound('Diskon tidak ditemukan');
            }

            $this->authorize('member', $outlet);

            $updatedDiscount = $this->serviceCatalogService->updateDiscount(
                $discount,
                $request->validated()
            );

            return $this->ok(new DiscountResource($updatedDiscount), 'Diskon berhasil diperbarui');
        } catch (Exception $e) {
            return $this->badRequest('Gagal memperbarui diskon: '.$e->getMessage());
        }
    }
}
