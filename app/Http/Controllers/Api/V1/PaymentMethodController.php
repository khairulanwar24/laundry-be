<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\PaymentMethod\StorePaymentMethodRequest;
use App\Http\Requests\Api\V1\PaymentMethod\UpdatePaymentMethodRequest;
use App\Http\Resources\Api\V1\PaymentMethodResource;
use App\Models\Outlet;
use App\Models\PaymentMethod;
use App\Services\Api\V1\PaymentMethodService;
use App\Support\ResponseJson;
use Exception;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

class PaymentMethodController extends Controller
{
    use AuthorizesRequests, ResponseJson;

    public function __construct(
        private PaymentMethodService $paymentMethodService
    ) {}

    /**
     * Display a listing of active payment methods for the outlet.
     */
    public function index(Outlet $outlet): JsonResponse
    {
        $this->authorize('viewAny', [PaymentMethod::class, $outlet]);

        $paymentMethods = $outlet->paymentMethods()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return $this->ok(PaymentMethodResource::collection($paymentMethods));
    }

    /**
     * Store a newly created payment method.
     */
    public function store(StorePaymentMethodRequest $request, Outlet $outlet): JsonResponse
    {
        try {
            $this->authorize('create', [PaymentMethod::class, $outlet]);

            $paymentMethod = $this->paymentMethodService->create(
                $outlet,
                $request->validated()
            );

            return $this->created(new PaymentMethodResource($paymentMethod), 'Metode pembayaran berhasil dibuat');
        } catch (Exception $e) {
            return $this->badRequest('Gagal membuat metode pembayaran: '.$e->getMessage());
        }
    }

    /**
     * Update the specified payment method.
     */
    public function update(
        UpdatePaymentMethodRequest $request,
        Outlet $outlet,
        PaymentMethod $paymentMethod
    ): JsonResponse {
        try {
            // Ensure payment method belongs to the outlet - return 404 if not
            if ($paymentMethod->outlet_id !== $outlet->id) {
                return $this->notFound('Metode pembayaran tidak ditemukan');
            }

            $this->authorize('update', $paymentMethod);

            $updatedPaymentMethod = $this->paymentMethodService->update(
                $paymentMethod,
                $request->validated()
            );

            return $this->ok(new PaymentMethodResource($updatedPaymentMethod), 'Metode pembayaran berhasil diperbarui');
        } catch (Exception $e) {
            return $this->badRequest('Gagal memperbarui metode pembayaran: '.$e->getMessage());
        }
    }

    /**
     * Remove the specified payment method.
     */
    public function destroy(Outlet $outlet, PaymentMethod $paymentMethod): JsonResponse
    {
        try {
            // Ensure payment method belongs to the outlet - return 404 if not
            if ($paymentMethod->outlet_id !== $outlet->id) {
                return $this->notFound('Metode pembayaran tidak ditemukan');
            }

            $this->authorize('delete', $paymentMethod);

            $this->paymentMethodService->delete($paymentMethod);

            return $this->ok(null, 'Metode pembayaran berhasil dihapus');
        } catch (Exception $e) {
            return $this->badRequest('Gagal menghapus metode pembayaran: '.$e->getMessage());
        }
    }
}
