<?php

namespace Tests\Unit;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Outlet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_belongs_to_outlet(): void
    {
        $outlet = Outlet::factory()->create();
        $customer = Customer::factory()->create(['outlet_id' => $outlet->id]);

        $this->assertInstanceOf(Outlet::class, $customer->outlet);
        $this->assertEquals($outlet->id, $customer->outlet->id);
    }

    public function test_customer_has_many_orders(): void
    {
        $customer = Customer::factory()->create();
        $orders = Order::factory(3)->create(['customer_id' => $customer->id]);

        $this->assertCount(3, $customer->orders);
        $this->assertInstanceOf(Order::class, $customer->orders->first());
    }

    public function test_customer_fillable_attributes(): void
    {
        $customer = new Customer;
        $expectedFillable = [
            'outlet_id',
            'name',
            'phone',
            'email',
            'address',
            'is_active',
        ];

        $this->assertEquals($expectedFillable, $customer->getFillable());
    }

    public function test_customer_casts(): void
    {
        $customer = Customer::factory()->create(['is_active' => 1]);

        $this->assertIsBool($customer->is_active);
        $this->assertTrue($customer->is_active);
    }

    public function test_customer_factory_creates_valid_customer(): void
    {
        $customer = Customer::factory()->create();

        $this->assertInstanceOf(Customer::class, $customer);
        $this->assertNotNull($customer->outlet_id);
        $this->assertNotNull($customer->name);
        $this->assertIsBool($customer->is_active);
    }
}
