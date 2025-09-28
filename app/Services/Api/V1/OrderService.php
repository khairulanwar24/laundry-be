<?php

namespace App\Services\Api\V1;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Perfume;
use App\Models\ServiceVariant;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        private InvoiceService $invoiceService
    ) {}

    /**
     * Create a new order with order items.
     *
     * @param  array  $data  Order data with items
     *
     * @throws Exception
     */
    public function createOrder(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            // Validate required fields
            $this->validateOrderData($data);

            // Create the order
            $order = Order::create([
                'outlet_id' => $data['outlet_id'],
                'customer_id' => $data['customer_id'],
                'invoice_no' => $this->invoiceService->generateInvoiceNumber($data['outlet_id']),
                'status' => $data['status'] ?? Order::STATUS_ANTRIAN,
                'payment_status' => $data['payment_status'] ?? Order::PAYMENT_STATUS_UNPAID,
                'checkin_at' => $data['checkin_at'] ?? now(),
                'eta_at' => $data['eta_at'] ?? null,
                'notes' => $data['notes'] ?? null,
                'perfume_id' => $data['perfume_id'] ?? null,
                'discount_value_snapshot' => $data['discount_value_snapshot'] ?? 0.00,
                'subtotal' => 0.00, // Will be calculated from items
                'total' => 0.00, // Will be calculated
                'created_by' => $data['created_by'] ?? null,
            ]);

            // Create order items and calculate totals
            $subtotal = 0.00;
            if (isset($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $itemData) {
                    $orderItem = $this->createOrderItem($order->id, $itemData);
                    $subtotal += $orderItem->line_total;
                }
            }

            // Update order totals
            $totalAmount = $subtotal - $order->discount_value_snapshot;
            $order->update([
                'subtotal' => $subtotal,
                'total' => $totalAmount,
            ]);

            // Create initial status history
            $this->createStatusHistory($order->id, null, $order->status, $data['created_by'] ?? null);

            return $order->fresh(['orderItems', 'customer', 'statusHistories', 'perfume']);
        });
    }

    /**
     * Create an order item for an order.
     *
     * @throws Exception
     */
    private function createOrderItem(int $orderId, array $itemData): OrderItem
    {
        // Validate service variant exists
        $serviceVariant = ServiceVariant::findOrFail($itemData['service_variant_id']);

        // Enrich item data with service variant details
        $enrichedItemData = [
            'order_id' => $orderId,
            'service_variant_id' => $itemData['service_variant_id'],
            'unit' => $serviceVariant->unit,
            'qty' => $itemData['qty'],
            'price_per_unit_snapshot' => $serviceVariant->price_per_unit,
            'line_total' => $itemData['qty'] * $serviceVariant->price_per_unit,
        ];

        return OrderItem::create($enrichedItemData);
    }

    /**
     * Update order status and create status history.
     *
     * @throws Exception
     */
    public function updateOrderStatus(int $orderId, string $newStatus, ?int $userId = null, ?string $notes = null): Order
    {
        return DB::transaction(function () use ($orderId, $newStatus, $userId, $notes) {
            $order = Order::findOrFail($orderId);
            $oldStatus = $order->status;

            // Validate status transition
            $this->validateStatusTransition($oldStatus, $newStatus);

            // Update order status
            $order->update(['status' => $newStatus]);

            // Create status history
            $this->createStatusHistory($orderId, $oldStatus, $newStatus, $userId, $notes);

            return $order->fresh(['statusHistories']);
        });
    }

    /**
     * Create status history record.
     */
    private function createStatusHistory(int $orderId, ?string $fromStatus, string $toStatus, ?int $userId = null, ?string $notes = null): OrderStatusHistory
    {
        return OrderStatusHistory::create([
            'order_id' => $orderId,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'by_user_id' => $userId,
            'notes' => $notes,
            'changed_at' => now(),
        ]);
    }

    /**
     * Process payment for an order.
     *
     * @throws Exception
     */
    public function processPayment(int $orderId, array $paymentData): Payment
    {
        return DB::transaction(function () use ($orderId, $paymentData) {
            $order = Order::findOrFail($orderId);

            // Check if order already has a payment
            if ($order->payment) {
                throw new Exception('Order already has a payment');
            }

            // Validate payment amount equals order total
            if ($paymentData['amount'] != $order->total) {
                throw new Exception('Payment amount must equal order total');
            }

            // Validate payment method exists
            $paymentMethod = PaymentMethod::findOrFail($paymentData['payment_method_id']);

            // Create payment
            $payment = Payment::create([
                'order_id' => $orderId,
                'method_id' => $paymentData['payment_method_id'],
                'amount' => $paymentData['amount'],
                'paid_at' => $paymentData['paid_at'] ?? now(),
                'ref_no' => $paymentData['ref_no'] ?? null,
                'note' => $paymentData['note'] ?? null,
                'status' => $paymentData['status'] ?? Payment::STATUS_SUCCESS,
            ]);

            // If payment is successful, update order status if still pending
            if ($payment->status === Payment::STATUS_SUCCESS && $order->status === Order::STATUS_ANTRIAN) {
                $this->updateOrderStatus($orderId, Order::STATUS_PROSES, $paymentData['processed_by_user_id'] ?? null, 'Payment processed successfully');
            }

            // Update order payment status
            if ($payment->status === Payment::STATUS_SUCCESS) {
                $order->update(['payment_status' => Order::PAYMENT_STATUS_PAID]);
            }

            return $payment;
        });
    }

    /**
     * Mark order as ready for pickup.
     *
     * @throws Exception
     */
    public function markOrderReady(int $orderId, ?int $userId = null, ?string $notes = null): Order
    {
        $order = Order::findOrFail($orderId);

        // Validate current status allows marking as ready
        if (! in_array($order->status, [Order::STATUS_ANTRIAN, Order::STATUS_PROSES])) {
            throw new Exception('Order cannot be marked as ready from current status: ' . $order->status);
        }

        return $this->updateOrderStatus($orderId, Order::STATUS_SIAP_DIAMBIL, $userId, $notes);
    }

    /**
     * Mark order as delivered/picked up.
     *
     * @throws Exception
     */
    public function markOrderDelivered(int $orderId, ?int $userId = null, ?string $notes = null): Order
    {
        $order = Order::findOrFail($orderId);

        // Validate current status allows delivery
        if ($order->status !== Order::STATUS_SIAP_DIAMBIL) {
            throw new Exception('Order must be ready for pickup before it can be delivered');
        }

        // Update the order status and set collected_at timestamp
        $updatedOrder = $this->updateOrderStatus($orderId, Order::STATUS_SELESAI, $userId, $notes);
        $updatedOrder->update(['collected_at' => now()]);

        return $updatedOrder->fresh();
    }

    /**
     * Cancel an order.
     *
     * @throws Exception
     */
    public function cancelOrder(int $orderId, ?int $userId = null, ?string $reason = null): Order
    {
        return DB::transaction(function () use ($orderId, $userId, $reason) {
            $order = Order::findOrFail($orderId);

            // Validate order can be cancelled
            if (in_array($order->status, [Order::STATUS_SELESAI, Order::STATUS_BATAL])) {
                throw new Exception('Order cannot be cancelled from current status: ' . $order->status);
            }

            // If order has a successful payment, handle refund logic here
            if ($order->payment && $order->payment->status === Payment::STATUS_SUCCESS) {
                // Update payment status to VOID for refund
                $order->payment->update(['status' => Payment::STATUS_VOID]);
            }

            return $this->updateOrderStatus($orderId, Order::STATUS_BATAL, $userId, $reason ?? 'Order cancelled');
        });
    }

    /**
     * Get orders with outstanding payments (tabs).
     */
    public function getOutstandingOrders(int $outletId): Collection
    {
        return Order::where('outlet_id', $outletId)
            ->whereDoesntHave('payment', function ($query) {
                $query->where('status', Payment::STATUS_SUCCESS);
            })
            ->whereNotIn('status', [Order::STATUS_BATAL, Order::STATUS_SELESAI])
            ->with(['customer', 'orderItems.serviceVariant'])
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Get orders by status for an outlet.
     */
    public function getOrdersByStatus(int $outletId, string $status): Collection
    {
        return Order::where('outlet_id', $outletId)
            ->where('status', $status)
            ->with(['customer', 'orderItems.serviceVariant', 'payment'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get order details with all relationships.
     */
    public function getOrderDetails(int $orderId): Order
    {
        return Order::with([
            'customer',
            'orderItems.serviceVariant',
            'statusHistories.byUser',
            'payment.paymentMethod',
            'perfume',
        ])->findOrFail($orderId);
    }

    /**
     * Validate order data.
     *
     * @throws Exception
     */
    private function validateOrderData(array $data): void
    {
        $requiredFields = ['outlet_id', 'customer_id'];

        foreach ($requiredFields as $field) {
            if (! isset($data[$field]) || empty($data[$field])) {
                throw new Exception("Required field missing: {$field}");
            }
        }

        // Validate customer exists and belongs to the outlet
        $customer = Customer::where('id', $data['customer_id'])
            ->where('outlet_id', $data['outlet_id'])
            ->first();

        if (! $customer) {
            throw new Exception('Customer not found or does not belong to the specified outlet');
        }

        // Validate perfume if provided
        if (isset($data['perfume_id']) && ! empty($data['perfume_id'])) {
            $perfume = Perfume::where('id', $data['perfume_id'])
                ->where('outlet_id', $data['outlet_id'])
                ->first();

            if (! $perfume) {
                throw new Exception('Perfume not found or does not belong to the specified outlet');
            }
        }

        // Validate items if provided
        if (isset($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $index => $item) {
                $this->validateOrderItemData($item, $index);
            }
        }
    }

    /**
     * Validate order item data.
     *
     * @throws Exception
     */
    private function validateOrderItemData(array $itemData, int $index): void
    {
        $requiredFields = ['service_variant_id', 'qty'];

        foreach ($requiredFields as $field) {
            if (! isset($itemData[$field])) {
                throw new Exception("Required field missing in item {$index}: {$field}");
            }
        }

        // Validate quantity is positive
        if ($itemData['qty'] <= 0) {
            throw new Exception("Quantity must be positive in item {$index}");
        }

        // Validate price is not negative (if provided)
        if (isset($itemData['price_per_unit_snapshot']) && $itemData['price_per_unit_snapshot'] < 0) {
            throw new Exception("Price cannot be negative in item {$index}");
        }

        // Validate unit is valid (if provided)
        if (isset($itemData['unit'])) {
            $validUnits = ['kg', 'pcs', 'meter'];
            if (! in_array($itemData['unit'], $validUnits)) {
                throw new Exception("Invalid unit in item {$index}: {$itemData['unit']}");
            }
        }

        // Validate service variant exists
        $serviceVariant = ServiceVariant::find($itemData['service_variant_id']);
        if (! $serviceVariant) {
            throw new Exception("Service variant not found in item {$index}: {$itemData['service_variant_id']}");
        }

        // Validate quantity for 'pcs' unit must be integer
        if ($serviceVariant->unit === 'pcs' && floor($itemData['qty']) != $itemData['qty']) {
            throw new Exception("Quantity for 'pcs' unit must be an integer in item {$index}");
        }
    }

    /**
     * Validate status transition.
     *
     * @throws Exception
     */
    private function validateStatusTransition(string $fromStatus, string $toStatus): void
    {
        $validTransitions = [
            Order::STATUS_ANTRIAN => [Order::STATUS_PROSES, Order::STATUS_BATAL],
            Order::STATUS_PROSES => [Order::STATUS_SIAP_DIAMBIL, Order::STATUS_BATAL],
            Order::STATUS_SIAP_DIAMBIL => [Order::STATUS_SELESAI, Order::STATUS_BATAL],
            Order::STATUS_SELESAI => [], // Terminal state
            Order::STATUS_BATAL => [], // Terminal state
        ];

        if (! isset($validTransitions[$fromStatus]) || ! in_array($toStatus, $validTransitions[$fromStatus])) {
            throw new Exception("Invalid status transition from {$fromStatus} to {$toStatus}");
        }
    }
}
