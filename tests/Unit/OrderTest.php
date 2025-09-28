<?php

namespace Tests\Unit;

use App\Models\Customer;
use App\Models\Discount;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Outlet;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Perfume;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_belongs_to_outlet(): void
    {
        $outlet = Outlet::factory()->create();
        $order = Order::factory()->create(['outlet_id' => $outlet->id]);

        $this->assertInstanceOf(Outlet::class, $order->outlet);
        $this->assertEquals($outlet->id, $order->outlet->id);
    }

    public function test_order_belongs_to_customer(): void
    {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create(['customer_id' => $customer->id]);

        $this->assertInstanceOf(Customer::class, $order->customer);
        $this->assertEquals($customer->id, $order->customer->id);
    }

    public function test_order_has_many_order_items(): void
    {
        $order = Order::factory()->create();
        $items = OrderItem::factory(3)->create(['order_id' => $order->id]);

        $this->assertCount(3, $order->orderItems);
        $this->assertInstanceOf(OrderItem::class, $order->orderItems->first());
    }

    public function test_order_has_many_status_histories(): void
    {
        $order = Order::factory()->create();
        $histories = OrderStatusHistory::factory(2)->create(['order_id' => $order->id]);

        $this->assertCount(2, $order->statusHistories);
        $this->assertInstanceOf(OrderStatusHistory::class, $order->statusHistories->first());
    }

    public function test_order_has_one_payment(): void
    {
        $order = Order::factory()->create();
        $payment = Payment::factory()->create(['order_id' => $order->id]);

        $this->assertInstanceOf(Payment::class, $order->payment);
        $this->assertEquals($payment->id, $order->payment->id);
    }

    public function test_order_constants(): void
    {
        $this->assertEquals('ANTRIAN', Order::STATUS_ANTRIAN);
        $this->assertEquals('PROSES', Order::STATUS_PROSES);
        $this->assertEquals('SIAP_DIAMBIL', Order::STATUS_SIAP_DIAMBIL);
        $this->assertEquals('SELESAI', Order::STATUS_SELESAI);
        $this->assertEquals('BATAL', Order::STATUS_BATAL);
        $this->assertEquals('UNPAID', Order::PAYMENT_STATUS_UNPAID);
        $this->assertEquals('PAID', Order::PAYMENT_STATUS_PAID);
    }

    public function test_order_casts(): void
    {
        $order = Order::factory()->create([
            'discount_value_snapshot' => '10.50',
            'subtotal' => '100.75',
            'total' => '90.25',
        ]);

        $this->assertIsString($order->discount_value_snapshot);
        $this->assertIsString($order->subtotal);
        $this->assertIsString($order->total);
        $this->assertEquals('10.50', $order->discount_value_snapshot);
    }

    public function test_order_optional_relationships(): void
    {
        $paymentMethod = PaymentMethod::factory()->create();
        $perfume = Perfume::factory()->create();
        $discount = Discount::factory()->create();
        $collectedBy = User::factory()->create();
        $createdBy = User::factory()->create();

        $order = Order::factory()->create([
            'payment_method_id' => $paymentMethod->id,
            'perfume_id' => $perfume->id,
            'discount_id' => $discount->id,
            'collected_by_user_id' => $collectedBy->id,
            'created_by' => $createdBy->id,
        ]);

        $this->assertInstanceOf(PaymentMethod::class, $order->paymentMethod);
        $this->assertInstanceOf(Perfume::class, $order->perfume);
        $this->assertInstanceOf(Discount::class, $order->discount);
        $this->assertInstanceOf(User::class, $order->collectedByUser);
        $this->assertInstanceOf(User::class, $order->createdByUser);
    }
}
