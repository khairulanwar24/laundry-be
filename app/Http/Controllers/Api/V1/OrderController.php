<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Order\ChangeStatusRequest;
use App\Http\Requests\Api\V1\Order\PayOrderRequest;
use App\Http\Requests\Api\V1\Order\StoreOrderRequest;
use App\Http\Resources\Api\V1\OrderResource;
use App\Models\Order;
use App\Models\Outlet;
use App\Services\Api\V1\OrderService;
use App\Support\ResponseJson;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    use AuthorizesRequests, ResponseJson;

    public function __construct(
        private OrderService $orderService
    ) {}

    /**
     * Display a listing of orders filtered by tab and search query.
     */
    public function index(Request $request, Outlet $outlet): JsonResponse
    {
        try {
            $this->authorize('viewAny', [Order::class, $outlet]);

            $tab = $request->input('tab', 'antrian');
            $search = $request->input('q');

            $query = Order::where('outlet_id', $outlet->id)
                ->with(['items.variant', 'customer', 'outlet']);

            // Filter by tab (status)
            switch ($tab) {
                case 'antrian':
                    $query->where('status', Order::STATUS_ANTRIAN);
                    break;
                case 'proses':
                    $query->where('status', Order::STATUS_PROSES);
                    break;
                case 'siap-ambil':
                    $query->where('status', Order::STATUS_SIAP_DIAMBIL);
                    break;
                case 'selesai':
                    $query->where('status', Order::STATUS_SELESAI);
                    break;
                case 'batal':
                    $query->where('status', Order::STATUS_BATAL);
                    break;
            }

            // Search functionality
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('invoice_no', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($customerQuery) use ($search) {
                            $customerQuery->where('phone', 'like', "%{$search}%")
                                ->orWhere('name', 'like', "%{$search}%");
                        });
                });
            }

            $orders = $query->latest('checkin_at')->paginate(15);

            return $this->ok(
                data: OrderResource::collection($orders)->response()->getData(),
                message: 'Daftar pesanan berhasil diambil'
            );
        } catch (Exception $e) {
            return $this->serverError(
                message: 'Gagal mengambil daftar pesanan'
            );
        }
    }

    /**
     * Store a newly created order.
     */
    public function store(StoreOrderRequest $request, Outlet $outlet): JsonResponse
    {
        try {
            $this->authorize('create', [Order::class, $outlet]);

            $data = $request->validated();
            $data['outlet_id'] = $outlet->id;
            $data['created_by'] = $request->user()->id;

            $order = $this->orderService->createOrder($data);
            $order->load(['items.serviceVariant', 'customer', 'outlet']);

            return $this->created(
                data: new OrderResource($order),
                message: 'Pesanan berhasil dibuat'
            );
        } catch (Exception $e) {
            Log::error('OrderController::store - Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            // Check if this is a validation error from business logic
            if (
                str_contains($e->getMessage(), 'must be an integer') ||
                str_contains($e->getMessage(), 'must be positive') ||
                str_contains($e->getMessage(), 'not found') ||
                str_contains($e->getMessage(), 'Required field missing')
            ) {
                return $this->fail(
                    message: $e->getMessage()
                );
            }

            return $this->serverError(
                message: 'Gagal membuat pesanan'
            );
        }
    }

    /**
     * Display the specified order.
     */
    public function show(Outlet $outlet, Order $order): JsonResponse
    {
        try {
            $this->authorize('view', [$order, $outlet]);

            // Ensure order belongs to the outlet
            if ($order->outlet_id !== $outlet->id) {
                return $this->notFound(
                    message: 'Pesanan tidak ditemukan'
                );
            }

            $order->load(['items.variant', 'customer', 'outlet']);

            return $this->ok(
                data: new OrderResource($order),
                message: 'Detail pesanan berhasil diambil'
            );
        } catch (AuthorizationException $e) {
            return $this->notFound(
                message: 'Pesanan tidak ditemukan'
            );
        } catch (Exception $e) {
            Log::error('OrderController::show - Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return $this->serverError(
                message: 'Gagal mengambil detail pesanan'
            );
        }
    }

    /**
     * Update order status.
     */
    public function status(ChangeStatusRequest $request, Outlet $outlet, Order $order): JsonResponse
    {
        try {
            $this->authorize('update', [$order, $outlet]);

            // Ensure order belongs to the outlet
            if ($order->outlet_id !== $outlet->id) {
                return $this->notFound(
                    message: 'Pesanan tidak ditemukan'
                );
            }

            $data = $request->validated();
            $updatedOrder = $this->orderService->updateOrderStatus($order->id, $data['to']);
            $updatedOrder->load(['items.variant', 'customer', 'outlet']);

            return $this->ok(
                data: new OrderResource($updatedOrder),
                message: 'Status pesanan berhasil diubah'
            );
        } catch (AuthorizationException $e) {
            return $this->notFound(
                message: 'Pesanan tidak ditemukan'
            );
        } catch (Exception $e) {
            Log::error('OrderController::status - Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return $this->serverError(
                message: 'Gagal mengubah status pesanan'
            );
        }
    }

    /**
     * Process payment for the order.
     */
    public function pay(PayOrderRequest $request, Outlet $outlet, Order $order): JsonResponse
    {
        try {
            $this->authorize('update', [$order, $outlet]);

            // Ensure order belongs to the outlet
            if ($order->outlet_id !== $outlet->id) {
                return $this->notFound(
                    message: 'Pesanan tidak ditemukan'
                );
            }

            $data = $request->validated();
            $payment = $this->orderService->processPayment($order->id, $data);

            $order->refresh();
            $order->load(['items.variant', 'customer', 'outlet']);

            return $this->ok(
                data: new OrderResource($order),
                message: 'Pembayaran berhasil diproses'
            );
        } catch (AuthorizationException $e) {
            return $this->notFound(
                message: 'Pesanan tidak ditemukan'
            );
        } catch (Exception $e) {
            Log::error('OrderController::pay - Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return $this->serverError(
                message: 'Gagal memproses pembayaran'
            );
        }
    }

    /**
     * Mark order as collected/picked up.
     */
    public function pickup(Outlet $outlet, Order $order): JsonResponse
    {
        try {
            $this->authorize('update', [$order, $outlet]);

            // Ensure order belongs to the outlet
            if ($order->outlet_id !== $outlet->id) {
                return $this->notFound(
                    message: 'Pesanan tidak ditemukan'
                );
            }

            $updatedOrder = $this->orderService->markOrderDelivered($order->id);
            $updatedOrder->load(['items.variant', 'customer', 'outlet']);

            return $this->ok(
                data: new OrderResource($updatedOrder),
                message: 'Pesanan berhasil ditandai sebagai diambil'
            );
        } catch (AuthorizationException $e) {
            return $this->notFound(
                message: 'Pesanan tidak ditemukan'
            );
        } catch (Exception $e) {
            Log::error('OrderController::pickup - Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return $this->serverError(
                message: 'Gagal menandai pesanan sebagai diambil'
            );
        }
    }
}
