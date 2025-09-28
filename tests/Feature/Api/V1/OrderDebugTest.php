<?php

namespace Tests\Feature\Api\V1;

use App\Models\Outlet;
use App\Models\User;
use App\Models\UserOutlet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderDebugTest extends TestCase
{
    use RefreshDatabase;

    public function test_basic_database_setup_works(): void
    {
        // Test if basic database operations work
        $user = User::factory()->create();
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_order_creation_with_full_data(): void
    {
        $user = User::factory()->create();
        $outlet = Outlet::factory()->create(['owner_user_id' => $user->id]);

        UserOutlet::factory()->create([
            'user_id' => $user->id,
            'outlet_id' => $outlet->id,
            'role' => UserOutlet::ROLE_OWNER,
        ]);

        // Create required dependencies
        $customer = \App\Models\Customer::factory()->create(['outlet_id' => $outlet->id]);
        $serviceVariant = \App\Models\ServiceVariant::factory()->create(['unit' => 'kg', 'price_per_unit' => 8000]);

        Sanctum::actingAs($user);

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

        $response = $this->postJson("/api/v1/outlets/{$outlet->id}/orders", $orderData);

        // Debug output
        echo 'Response Status: '.$response->status()."\n";
        echo 'Response Body: '.$response->getContent()."\n";

        // Assert success
        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'message' => 'Pesanan berhasil dibuat',
        ]);
    }
}
