<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_fillable_attributes(): void
    {
        $expectedFillable = [
            'order_id',
            'method_id',
            'amount',
            'paid_at',
            'ref_no',
            'note',
            'status',
        ];

        $payment = new Payment;

        $this->assertEquals($expectedFillable, $payment->getFillable());
    }

    public function test_casts_attributes(): void
    {
        $expectedCasts = [
            'id' => 'int',
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];

        $payment = new Payment;

        foreach ($expectedCasts as $attribute => $expectedCast) {
            $this->assertEquals($expectedCast, $payment->getCasts()[$attribute]);
        }
    }

    public function test_belongs_to_order_relationship(): void
    {
        $order = Order::factory()->create();
        $payment = Payment::factory()->create(['order_id' => $order->id]);

        $this->assertInstanceOf(Order::class, $payment->order);
        $this->assertEquals($order->id, $payment->order->id);
    }

    public function test_can_create_payment_with_valid_data(): void
    {
        $order = Order::factory()->create();
        $paymentMethod = \App\Models\PaymentMethod::factory()->create();

        $paymentData = [
            'order_id' => $order->id,
            'method_id' => $paymentMethod->id,
            'amount' => 25000.00,
            'paid_at' => now(),
            'ref_no' => 'TXN-12345',
            'note' => 'Cash payment',
            'status' => 'SUCCESS',
        ];

        $payment = Payment::create($paymentData);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'method_id' => $paymentMethod->id,
            'amount' => 25000.00,
            'ref_no' => 'TXN-12345',
            'status' => 'SUCCESS',
        ]);

        $this->assertEquals(25000.00, $payment->amount);
        $this->assertEquals('SUCCESS', $payment->status);
        $this->assertEquals('TXN-12345', $payment->ref_no);
    }

    public function test_payment_status_enum_values(): void
    {
        $validPaymentStatuses = ['SUCCESS', 'VOID'];

        foreach ($validPaymentStatuses as $paymentStatus) {
            $payment = Payment::factory()->create(['status' => $paymentStatus]);
            $this->assertEquals($paymentStatus, $payment->status);
        }
    }

    public function test_paid_at_is_cast_to_datetime(): void
    {
        $paidAt = now();

        $payment = Payment::factory()->create(['paid_at' => $paidAt]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $payment->paid_at);
        // Use seconds precision for comparison
        $this->assertTrue($paidAt->format('Y-m-d H:i:s') === $payment->paid_at->format('Y-m-d H:i:s'));
    }

    public function test_can_create_without_optional_fields(): void
    {
        $order = Order::factory()->create();
        $paymentMethod = \App\Models\PaymentMethod::factory()->create();

        $paymentData = [
            'order_id' => $order->id,
            'method_id' => $paymentMethod->id,
            'amount' => 15000.00,
            'paid_at' => now(), // paid_at is required
            'status' => 'SUCCESS',
        ];

        $payment = Payment::create($paymentData);

        $this->assertDatabaseHas('payments', $paymentData);
        $this->assertEquals('SUCCESS', $payment->status);
        $this->assertNull($payment->ref_no);
        $this->assertNull($payment->note);
    }

    public function test_belongs_to_payment_method_relationship(): void
    {
        $paymentMethod = \App\Models\PaymentMethod::factory()->create();
        $payment = Payment::factory()->create(['method_id' => $paymentMethod->id]);

        $this->assertInstanceOf(\App\Models\PaymentMethod::class, $payment->paymentMethod);
        $this->assertEquals($paymentMethod->id, $payment->paymentMethod->id);
    }

    public function test_has_timestamps(): void
    {
        $payment = Payment::factory()->create();

        $this->assertNotNull($payment->created_at);
        $this->assertNotNull($payment->updated_at);
    }
}
