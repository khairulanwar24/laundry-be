<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\OrderStatusHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderStatusHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_fillable_attributes(): void
    {
        $expectedFillable = [
            'order_id',
            'from_status',
            'to_status',
            'by_user_id',
            'notes',
            'changed_at',
        ];

        $orderStatusHistory = new OrderStatusHistory;

        $this->assertEquals($expectedFillable, $orderStatusHistory->getFillable());
    }

    public function test_casts_attributes(): void
    {
        $expectedCasts = [
            'id' => 'int',
            'changed_at' => 'datetime',
        ];

        $orderStatusHistory = new OrderStatusHistory;

        foreach ($expectedCasts as $attribute => $expectedCast) {
            $this->assertEquals($expectedCast, $orderStatusHistory->getCasts()[$attribute]);
        }
    }

    public function test_belongs_to_order_relationship(): void
    {
        $order = Order::factory()->create();
        $statusHistory = OrderStatusHistory::factory()->create(['order_id' => $order->id]);

        $this->assertInstanceOf(Order::class, $statusHistory->order);
        $this->assertEquals($order->id, $statusHistory->order->id);
    }

    public function test_can_create_order_status_history_with_valid_data(): void
    {
        $order = Order::factory()->create();
        $user = \App\Models\User::factory()->create();

        $statusHistoryData = [
            'order_id' => $order->id,
            'from_status' => 'PENDING',
            'to_status' => 'CONFIRMED',
            'by_user_id' => $user->id,
            'notes' => 'Order confirmed by staff',
            'changed_at' => now(),
        ];

        $statusHistory = OrderStatusHistory::create($statusHistoryData);

        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'from_status' => 'PENDING',
            'to_status' => 'CONFIRMED',
            'by_user_id' => $user->id,
            'notes' => 'Order confirmed by staff',
        ]);
        $this->assertEquals('PENDING', $statusHistory->from_status);
        $this->assertEquals('CONFIRMED', $statusHistory->to_status);
        $this->assertEquals($user->id, $statusHistory->by_user_id);
    }

    public function test_can_create_without_optional_fields(): void
    {
        $order = Order::factory()->create();
        $user = \App\Models\User::factory()->create();

        $statusHistoryData = [
            'order_id' => $order->id,
            'from_status' => 'PENDING',
            'to_status' => 'CONFIRMED',
            'by_user_id' => $user->id,
        ];

        $statusHistory = OrderStatusHistory::create($statusHistoryData);

        $this->assertDatabaseHas('order_status_histories', $statusHistoryData);
        $this->assertEquals('CONFIRMED', $statusHistory->to_status);
        $this->assertNull($statusHistory->notes);
    }

    public function test_belongs_to_user_relationship(): void
    {
        $user = \App\Models\User::factory()->create();
        $statusHistory = OrderStatusHistory::factory()->create(['by_user_id' => $user->id]);

        $this->assertInstanceOf(\App\Models\User::class, $statusHistory->byUser);
        $this->assertEquals($user->id, $statusHistory->byUser->id);
    }

    public function test_has_timestamps(): void
    {
        $statusHistory = OrderStatusHistory::factory()->create();

        $this->assertNotNull($statusHistory->created_at);
        $this->assertNotNull($statusHistory->updated_at);
    }
}
