<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_fillable_attributes(): void
    {
        $expectedFillable = [
            'order_id',
            'service_variant_id',
            'unit',
            'qty',
            'price_per_unit_snapshot',
            'line_total',
            'note',
        ];

        $orderItem = new OrderItem;

        $this->assertEquals($expectedFillable, $orderItem->getFillable());
    }

    public function test_casts_attributes(): void
    {
        $expectedCasts = [
            'id' => 'int',
            'qty' => 'decimal:2',
            'price_per_unit_snapshot' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];

        $orderItem = new OrderItem;

        foreach ($expectedCasts as $attribute => $expectedCast) {
            $this->assertEquals($expectedCast, $orderItem->getCasts()[$attribute]);
        }
    }

    public function test_belongs_to_order_relationship(): void
    {
        $order = Order::factory()->create();
        $orderItem = OrderItem::factory()->create(['order_id' => $order->id]);

        $this->assertInstanceOf(Order::class, $orderItem->order);
        $this->assertEquals($order->id, $orderItem->order->id);
    }

    public function test_can_create_order_item_with_valid_data(): void
    {
        $order = Order::factory()->create();

        // We need a service variant - let's create one first
        $serviceVariant = \App\Models\ServiceVariant::factory()->create();

        $orderItemData = [
            'order_id' => $order->id,
            'service_variant_id' => $serviceVariant->id,
            'unit' => 'kg',
            'qty' => 2.50,
            'price_per_unit_snapshot' => 5000.00,
            'line_total' => 12500.00,
            'note' => 'Handle with care',
        ];

        $orderItem = OrderItem::create($orderItemData);

        $this->assertDatabaseHas('order_items', $orderItemData);
        $this->assertEquals($serviceVariant->id, $orderItem->service_variant_id);
        $this->assertEquals('kg', $orderItem->unit);
        $this->assertEquals(2.50, $orderItem->qty);
        $this->assertEquals(5000.00, $orderItem->price_per_unit_snapshot);
        $this->assertEquals(12500.00, $orderItem->line_total);
    }

    public function test_unit_enum_values(): void
    {
        $validUnits = ['kg', 'pcs', 'meter'];

        foreach ($validUnits as $unit) {
            $orderItem = OrderItem::factory()->create(['unit' => $unit]);
            $this->assertEquals($unit, $orderItem->unit);
        }
    }

    public function test_belongs_to_service_variant_relationship(): void
    {
        $serviceVariant = \App\Models\ServiceVariant::factory()->create();
        $orderItem = OrderItem::factory()->create(['service_variant_id' => $serviceVariant->id]);

        $this->assertInstanceOf(\App\Models\ServiceVariant::class, $orderItem->serviceVariant);
        $this->assertEquals($serviceVariant->id, $orderItem->serviceVariant->id);
    }
}
