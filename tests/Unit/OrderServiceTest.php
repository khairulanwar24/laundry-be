<?php

namespace Tests\Unit;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Perfume;
use App\Models\ServiceVariant;
use App\Models\User;
use App\Services\Api\V1\InvoiceService;
use App\Services\Api\V1\OrderService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    private OrderService $orderService;

    protected function setUp(): void
    {
        parent::setUp();

        // Use real InvoiceService since it's simple and doesn't need external dependencies
        $this->orderService = app(OrderService::class);
    }

    public function test_create_order_successfully(): void
    {
        $customer = Customer::factory()->create();
        $serviceVariant = ServiceVariant::factory()->create(['price_per_unit' => 5000.00]);
        $perfume = Perfume::factory()->create(['outlet_id' => $customer->outlet_id]);

        $orderData = [
            'outlet_id' => $customer->outlet_id,
            'customer_id' => $customer->id,
            'status' => Order::STATUS_ANTRIAN,
            'payment_status' => Order::PAYMENT_STATUS_UNPAID,
            'notes' => 'Test order notes',
            'perfume_id' => $perfume->id,
            'created_by' => 1,
            'created_by_user_id' => 1,
            'items' => [
                [
                    'service_variant_id' => $serviceVariant->id,
                    'unit' => 'kg',
                    'qty' => 2.5,
                    'price_per_unit_snapshot' => 5000.00,
                ],
            ],
        ];

        $order = $this->orderService->createOrder($orderData);

        $this->assertInstanceOf(Order::class, $order);
        $this->assertNotEmpty($order->invoice_no);
        $this->assertEquals($customer->outlet_id, $order->outlet_id);
        $this->assertEquals($customer->id, $order->customer_id);
        $this->assertEquals(Order::STATUS_ANTRIAN, $order->status);
        $this->assertEquals('Test order notes', $order->notes);
        $this->assertEquals($perfume->id, $order->perfume_id);
        $this->assertEquals(12500.00, $order->subtotal);
        $this->assertEquals(12500.00, $order->total);

        // Check order item was created
        $this->assertCount(1, $order->orderItems);
        $orderItem = $order->orderItems->first();
        $this->assertEquals($serviceVariant->id, $orderItem->service_variant_id);
        $this->assertEquals(2.5, $orderItem->qty);
        $this->assertEquals(5000.00, $orderItem->price_per_unit_snapshot);
        $this->assertEquals(12500.00, $orderItem->line_total);

        // Check status history was created
        $this->assertCount(1, $order->statusHistories);
        $statusHistory = $order->statusHistories->first();
        $this->assertNull($statusHistory->from_status);
        $this->assertEquals(Order::STATUS_ANTRIAN, $statusHistory->to_status);
    }

    public function test_create_order_with_discount(): void
    {
        $customer = Customer::factory()->create();
        $serviceVariant = ServiceVariant::factory()->create(['price_per_unit' => 3000.00]);

        $orderData = [
            'outlet_id' => $customer->outlet_id,
            'customer_id' => $customer->id,
            'discount_value_snapshot' => 2000.00,
            'created_by' => 1,
            'created_by_user_id' => 1,
            'items' => [
                [
                    'service_variant_id' => $serviceVariant->id,
                    'unit' => 'pcs',
                    'qty' => 5,
                    'price_per_unit_snapshot' => 3000.00,
                ],
            ],
        ];

        $order = $this->orderService->createOrder($orderData);

        $this->assertEquals(15000.00, $order->subtotal);
        $this->assertEquals(13000.00, $order->total); // 15000 - 2000 discount
        $this->assertEquals(2000.00, $order->discount_value_snapshot);
    }

    public function test_create_order_fails_without_required_fields(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Required field missing: outlet_id');

        $this->orderService->createOrder([
            'customer_id' => 1,
        ]);
    }

    public function test_create_order_fails_with_invalid_customer(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Customer not found or does not belong to the specified outlet');

        $this->orderService->createOrder([
            'outlet_id' => 999,
            'customer_id' => 999,
        ]);
    }

    public function test_create_order_fails_with_invalid_perfume(): void
    {
        $customer = Customer::factory()->create();
        $perfume = Perfume::factory()->create(); // Different outlet

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Perfume not found or does not belong to the specified outlet');

        $this->orderService->createOrder([
            'outlet_id' => $customer->outlet_id,
            'customer_id' => $customer->id,
            'perfume_id' => $perfume->id,
        ]);
    }

    public function test_update_order_status_successfully(): void
    {
        $order = Order::factory()->create(['status' => Order::STATUS_ANTRIAN]);
        $user = User::factory()->create();

        $updatedOrder = $this->orderService->updateOrderStatus(
            $order->id,
            Order::STATUS_PROSES,
            $user->id,
            'Order processing started'
        );

        $this->assertEquals(Order::STATUS_PROSES, $updatedOrder->status);

        // Check status history was created
        $statusHistory = $updatedOrder->statusHistories->first();
        $this->assertEquals(Order::STATUS_ANTRIAN, $statusHistory->from_status);
        $this->assertEquals(Order::STATUS_PROSES, $statusHistory->to_status);
        $this->assertEquals($user->id, $statusHistory->by_user_id);
        $this->assertEquals('Order processing started', $statusHistory->notes);
    }

    public function test_update_order_status_fails_with_invalid_transition(): void
    {
        $order = Order::factory()->create(['status' => Order::STATUS_SELESAI]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid status transition from SELESAI to PROSES');

        $this->orderService->updateOrderStatus($order->id, Order::STATUS_PROSES);
    }

    public function test_process_payment_successfully(): void
    {
        $order = Order::factory()->create(['status' => Order::STATUS_ANTRIAN, 'total' => 25000.00]);
        $paymentMethod = PaymentMethod::factory()->create();

        $paymentData = [
            'payment_method_id' => $paymentMethod->id,
            'amount' => 25000.00, // Match the order total
            'ref_no' => 'PAY-12345',
            'note' => 'Cash payment',
            'status' => Payment::STATUS_SUCCESS,
            'processed_by_user_id' => 1,
        ];

        $payment = $this->orderService->processPayment($order->id, $paymentData);

        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertEquals($order->id, $payment->order_id);
        $this->assertEquals($paymentMethod->id, $payment->method_id);
        $this->assertEquals(25000.00, $payment->amount);
        $this->assertEquals(Payment::STATUS_SUCCESS, $payment->status);

        // Check that order status was updated to PROSES since payment was successful
        $order->refresh();
        $this->assertEquals(Order::STATUS_PROSES, $order->status);
        $this->assertEquals(Order::PAYMENT_STATUS_PAID, $order->payment_status);
    }

    public function test_process_payment_fails_when_order_already_has_payment(): void
    {
        $order = Order::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create();

        // Create existing payment
        Payment::factory()->create(['order_id' => $order->id]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Order already has a payment');

        $this->orderService->processPayment($order->id, [
            'method_id' => $paymentMethod->id,
            'amount' => 25000.00,
        ]);
    }

    public function test_mark_order_ready_successfully(): void
    {
        $order = Order::factory()->create(['status' => Order::STATUS_PROSES]);
        $user = User::factory()->create();

        $updatedOrder = $this->orderService->markOrderReady(
            $order->id,
            $user->id,
            'Order ready for pickup'
        );

        $this->assertEquals(Order::STATUS_SIAP_DIAMBIL, $updatedOrder->status);
    }

    public function test_mark_order_ready_fails_from_invalid_status(): void
    {
        $order = Order::factory()->create(['status' => Order::STATUS_SELESAI]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Order cannot be marked as ready from current status: SELESAI');

        $this->orderService->markOrderReady($order->id);
    }

    public function test_mark_order_delivered_successfully(): void
    {
        $order = Order::factory()->create(['status' => Order::STATUS_SIAP_DIAMBIL]);
        $user = User::factory()->create();

        $updatedOrder = $this->orderService->markOrderDelivered(
            $order->id,
            $user->id,
            'Order picked up by customer'
        );

        $this->assertEquals(Order::STATUS_SELESAI, $updatedOrder->status);
    }

    public function test_mark_order_delivered_fails_from_invalid_status(): void
    {
        $order = Order::factory()->create(['status' => Order::STATUS_PROSES]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Order must be ready for pickup before it can be delivered');

        $this->orderService->markOrderDelivered($order->id);
    }

    public function test_cancel_order_successfully(): void
    {
        $order = Order::factory()->create(['status' => Order::STATUS_ANTRIAN]);
        $user = User::factory()->create();

        $cancelledOrder = $this->orderService->cancelOrder(
            $order->id,
            $user->id,
            'Customer requested cancellation'
        );

        $this->assertEquals(Order::STATUS_BATAL, $cancelledOrder->status);
    }

    public function test_cancel_order_with_payment_voids_payment(): void
    {
        $order = Order::factory()->create(['status' => Order::STATUS_PROSES]);
        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'status' => Payment::STATUS_SUCCESS,
        ]);
        $user = User::factory()->create();

        $cancelledOrder = $this->orderService->cancelOrder(
            $order->id,
            $user->id,
            'Refund requested'
        );

        $this->assertEquals(Order::STATUS_BATAL, $cancelledOrder->status);

        $payment->refresh();
        $this->assertEquals(Payment::STATUS_VOID, $payment->status);
    }

    public function test_cancel_order_fails_from_completed_status(): void
    {
        $order = Order::factory()->create(['status' => Order::STATUS_SELESAI]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Order cannot be cancelled from current status: SELESAI');

        $this->orderService->cancelOrder($order->id);
    }

    public function test_get_outstanding_orders(): void
    {
        $customer = Customer::factory()->create();

        // Create orders with different payment statuses
        $unpaidOrder = Order::factory()->create([
            'outlet_id' => $customer->outlet_id,
            'status' => Order::STATUS_ANTRIAN, // Make sure it's not BATAL or SELESAI
        ]);
        $paidOrder = Order::factory()->create([
            'outlet_id' => $customer->outlet_id,
            'status' => Order::STATUS_PROSES,
        ]);
        $cancelledOrder = Order::factory()->create([
            'outlet_id' => $customer->outlet_id,
            'status' => Order::STATUS_BATAL,
        ]);

        // Create payments
        Payment::factory()->create(['order_id' => $paidOrder->id, 'status' => Payment::STATUS_SUCCESS]);

        $outstandingOrders = $this->orderService->getOutstandingOrders($customer->outlet_id);

        $this->assertCount(1, $outstandingOrders);
        $this->assertEquals($unpaidOrder->id, $outstandingOrders->first()->id);
    }

    public function test_get_orders_by_status(): void
    {
        $customer = Customer::factory()->create();

        Order::factory()->count(3)->create([
            'outlet_id' => $customer->outlet_id,
            'status' => Order::STATUS_PROSES,
        ]);
        Order::factory()->count(2)->create([
            'outlet_id' => $customer->outlet_id,
            'status' => Order::STATUS_SIAP_DIAMBIL,
        ]);

        $processingOrders = $this->orderService->getOrdersByStatus($customer->outlet_id, Order::STATUS_PROSES);
        $readyOrders = $this->orderService->getOrdersByStatus($customer->outlet_id, Order::STATUS_SIAP_DIAMBIL);

        $this->assertCount(3, $processingOrders);
        $this->assertCount(2, $readyOrders);
    }

    public function test_get_order_details(): void
    {
        $order = Order::factory()->create();
        OrderItem::factory()->create(['order_id' => $order->id]);
        OrderStatusHistory::factory()->create(['order_id' => $order->id]);
        Payment::factory()->create(['order_id' => $order->id]);

        $orderDetails = $this->orderService->getOrderDetails($order->id);

        $this->assertInstanceOf(Order::class, $orderDetails);
        $this->assertTrue($orderDetails->relationLoaded('customer'));
        $this->assertTrue($orderDetails->relationLoaded('orderItems'));
        $this->assertTrue($orderDetails->relationLoaded('statusHistories'));
        $this->assertTrue($orderDetails->relationLoaded('payment'));
        $this->assertTrue($orderDetails->relationLoaded('perfume'));
    }

    public function test_validate_order_item_data_fails_with_invalid_unit(): void
    {
        $customer = Customer::factory()->create();
        $serviceVariant = ServiceVariant::factory()->create(['unit' => 'kg']);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid unit in item 0: invalid_unit');

        $this->orderService->createOrder([
            'outlet_id' => $customer->outlet_id,
            'customer_id' => $customer->id,
            'items' => [
                [
                    'service_variant_id' => $serviceVariant->id,
                    'unit' => 'invalid_unit',
                    'qty' => 1,
                    'price_per_unit_snapshot' => 1000,
                ],
            ],
        ]);
    }

    public function test_validate_order_item_data_fails_with_negative_quantity(): void
    {
        $customer = Customer::factory()->create();
        $serviceVariant = ServiceVariant::factory()->create();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Quantity must be positive in item 0');

        $this->orderService->createOrder([
            'outlet_id' => $customer->outlet_id,
            'customer_id' => $customer->id,
            'items' => [
                [
                    'service_variant_id' => $serviceVariant->id,
                    'unit' => 'kg',
                    'qty' => -1,
                    'price_per_unit_snapshot' => 1000,
                ],
            ],
        ]);
    }

    public function test_validate_order_item_data_fails_with_negative_price(): void
    {
        $customer = Customer::factory()->create();
        $serviceVariant = ServiceVariant::factory()->create();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Price cannot be negative in item 0');

        $this->orderService->createOrder([
            'outlet_id' => $customer->outlet_id,
            'customer_id' => $customer->id,
            'items' => [
                [
                    'service_variant_id' => $serviceVariant->id,
                    'unit' => 'kg',
                    'qty' => 1,
                    'price_per_unit_snapshot' => -1000,
                ],
            ],
        ]);
    }
}
