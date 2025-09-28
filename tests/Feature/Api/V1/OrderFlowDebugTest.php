<?php

namespace Tests\Feature\Api\V1;

use App\Models\Customer;
use App\Models\Outlet;
use App\Models\ServiceVariant;
use App\Models\User;
use App\Models\UserOutlet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderFlowDebugTest extends TestCase
{
    use RefreshDatabase;

    public function test_debug_order_creation(): void
    {
        // Create user and outlet
        $user = User::factory()->create();
        $outlet = Outlet::factory()->create(['owner_user_id' => $user->id]);

        // Create user-outlet relationship
        UserOutlet::factory()->create([
            'user_id' => $user->id,
            'outlet_id' => $outlet->id,
            'role' => UserOutlet::ROLE_OWNER,
        ]);

        // Create test data
        $customer = Customer::factory()->create(['outlet_id' => $outlet->id]);
        $serviceVariant = ServiceVariant::factory()->create(['unit' => 'kg', 'price_per_unit' => 8000]);

        // Authenticate user
        Sanctum::actingAs($user);

        // Check if user is authenticated
        $authResponse = $this->getJson('/api/v1/auth/me');
        echo "\nAuth check status: ".$authResponse->status();
        echo "\nAuth check body: ".$authResponse->getContent();

        // Check outlet access
        $outletResponse = $this->getJson("/api/v1/outlets/{$outlet->id}");
        echo "\nOutlet access status: ".$outletResponse->status();
        echo "\nOutlet access body: ".$outletResponse->getContent();

        $orderData = [
            'customer_id' => $customer->id,
            'items' => [
                [
                    'service_variant_id' => $serviceVariant->id,
                    'qty' => 2.5,
                    'note' => 'Test laundry',
                ],
            ],
            'notes' => 'Test order',
        ];

        echo "\nOrder data: ".json_encode($orderData);

        $response = $this->postJson("/api/v1/outlets/{$outlet->id}/orders", $orderData);

        echo "\nResponse Status: ".$response->status();
        echo "\nResponse Body: ".$response->getContent();
        echo "\nResponse Headers: ".json_encode($response->headers->all());

        $this->assertEquals(201, $response->status(), 'Order creation should succeed');
    }
}
