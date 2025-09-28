<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Perfume\StorePerfumeRequest;
use App\Http\Requests\Api\V1\Perfume\UpdatePerfumeRequest;
use App\Http\Resources\Api\V1\PerfumeResource;
use App\Models\Outlet;
use App\Models\Perfume;
use App\Services\Api\V1\ServiceCatalogService;
use App\Support\ResponseJson;
use Exception;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

class PerfumeController extends Controller
{
    use AuthorizesRequests, ResponseJson;

    public function __construct(
        private ServiceCatalogService $serviceCatalogService
    ) {}

    /**
     * Display a listing of active perfumes for the outlet.
     */
    public function index(Outlet $outlet): JsonResponse
    {
        $this->authorize('member', $outlet);

        $perfumes = Perfume::where('outlet_id', $outlet->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return $this->ok(PerfumeResource::collection($perfumes));
    }

    /**
     * Store a newly created perfume.
     */
    public function store(StorePerfumeRequest $request, Outlet $outlet): JsonResponse
    {
        try {
            $this->authorize('member', $outlet);

            $perfume = $this->serviceCatalogService->createPerfume(
                $outlet,
                $request->validated()
            );

            return $this->created(new PerfumeResource($perfume), 'Parfum berhasil dibuat');
        } catch (Exception $e) {
            return $this->badRequest('Gagal membuat parfum: '.$e->getMessage());
        }
    }

    /**
     * Update the specified perfume.
     */
    public function update(
        UpdatePerfumeRequest $request,
        Outlet $outlet,
        Perfume $perfume
    ): JsonResponse {
        try {
            // Ensure perfume belongs to the outlet - return 404 if not
            if ($perfume->outlet_id !== $outlet->id) {
                return $this->notFound('Parfum tidak ditemukan');
            }

            $this->authorize('member', $outlet);

            $updatedPerfume = $this->serviceCatalogService->updatePerfume(
                $perfume,
                $request->validated()
            );

            return $this->ok(new PerfumeResource($updatedPerfume), 'Parfum berhasil diperbarui');
        } catch (Exception $e) {
            return $this->badRequest('Gagal memperbarui parfum: '.$e->getMessage());
        }
    }
}
