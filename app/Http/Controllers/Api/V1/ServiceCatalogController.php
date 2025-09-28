<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Service\StoreServiceRequest;
use App\Http\Requests\Api\V1\Service\UpdateServiceRequest;
use App\Http\Requests\Api\V1\ServiceVariant\StoreServiceVariantRequest;
use App\Http\Requests\Api\V1\ServiceVariant\UpdateServiceVariantRequest;
use App\Http\Resources\Api\V1\ServiceResource;
use App\Http\Resources\Api\V1\ServiceVariantResource;
use App\Models\Outlet;
use App\Models\Service;
use App\Models\ServiceVariant;
use App\Services\Api\V1\ServiceCatalogService;
use App\Support\ResponseJson;
use Exception;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceCatalogController extends Controller
{
    use AuthorizesRequests, ResponseJson;

    public function __construct(
        private ServiceCatalogService $serviceCatalogService
    ) {}

    /**
     * Display a listing of active services with variants for the outlet.
     */
    public function index(Request $request, Outlet $outlet): JsonResponse
    {
        $this->authorize('member', $outlet);

        $query = $request->get('q');
        $services = $this->serviceCatalogService->listServices($outlet, $query);

        return $this->ok(ServiceResource::collection($services));
    }

    /**
     * Store a newly created service.
     */
    public function store(StoreServiceRequest $request, Outlet $outlet): JsonResponse
    {
        try {
            $this->authorize('member', $outlet);

            $service = $this->serviceCatalogService->createService(
                $outlet,
                $request->validated()
            );

            return $this->created(new ServiceResource($service), 'Layanan berhasil dibuat');
        } catch (Exception $e) {
            return $this->badRequest('Gagal membuat layanan: '.$e->getMessage());
        }
    }

    /**
     * Update the specified service.
     */
    public function update(
        UpdateServiceRequest $request,
        Outlet $outlet,
        Service $service
    ): JsonResponse {
        try {
            // Ensure service belongs to the outlet - return 404 if not
            if ($service->outlet_id !== $outlet->id) {
                return $this->notFound('Layanan tidak ditemukan');
            }

            $this->authorize('member', $outlet);

            $updatedService = $this->serviceCatalogService->updateService(
                $service,
                $request->validated()
            );

            return $this->ok(new ServiceResource($updatedService), 'Layanan berhasil diperbarui');
        } catch (Exception $e) {
            return $this->badRequest('Gagal memperbarui layanan: '.$e->getMessage());
        }
    }

    /**
     * Store a newly created service variant.
     */
    public function storeVariant(
        StoreServiceVariantRequest $request,
        Outlet $outlet,
        Service $service
    ): JsonResponse {
        try {
            // Ensure service belongs to the outlet - return 404 if not
            if ($service->outlet_id !== $outlet->id) {
                return $this->notFound('Layanan tidak ditemukan');
            }

            $this->authorize('member', $outlet);

            $variant = $this->serviceCatalogService->createVariant(
                $service,
                $request->validated()
            );

            return $this->created(new ServiceVariantResource($variant), 'Varian layanan berhasil dibuat');
        } catch (Exception $e) {
            return $this->badRequest('Gagal membuat varian layanan: '.$e->getMessage());
        }
    }

    /**
     * Update the specified service variant.
     */
    public function updateVariant(
        UpdateServiceVariantRequest $request,
        Outlet $outlet,
        ServiceVariant $variant
    ): JsonResponse {
        try {
            // Check if variant's service belongs to the outlet by querying directly
            $service = Service::find($variant->service_id);
            if (! $service || $service->outlet_id !== $outlet->id) {
                return $this->notFound('Varian layanan tidak ditemukan');
            }

            $this->authorize('member', $outlet);

            $updatedVariant = $this->serviceCatalogService->updateVariant(
                $variant,
                $request->validated()
            );

            return $this->ok(new ServiceVariantResource($updatedVariant), 'Varian layanan berhasil diperbarui');
        } catch (Exception $e) {
            return $this->badRequest('Gagal memperbarui varian layanan: '.$e->getMessage());
        }
    }
}
