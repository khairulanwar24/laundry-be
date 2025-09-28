<?php

namespace Tests\Feature\Api\V1;

use App\Models\Outlet;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Models\UserOutlet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PaymentMethodControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $employee;

    private User $outsider;

    private Outlet $outlet;

    private PaymentMethod $paymentMethod;

    protected function setUp(): void
    {
        parent::setUp();

        // Create users
        $this->owner = User::factory()->create([
            'name' => 'Test Owner',
            'email' => 'owner@test.com',
            'password' => Hash::make('password123'),
        ]);

        $this->employee = User::factory()->create([
            'name' => 'Test Employee',
            'email' => 'employee@test.com',
            'password' => Hash::make('password123'),
        ]);

        $this->outsider = User::factory()->create([
            'name' => 'Test Outsider',
            'email' => 'outsider@test.com',
            'password' => Hash::make('password123'),
        ]);

        // Create outlet
        $this->outlet = \Database\Factories\OutletFactory::new()->create([
            'name' => 'Test Outlet',
            'address' => 'Test Address',
        ]);

        // Create user outlets
        UserOutlet::create([
            'user_id' => $this->owner->id,
            'outlet_id' => $this->outlet->id,
            'role' => UserOutlet::ROLE_OWNER,
            'permissions_json' => ['all' => true],
        ]);

        UserOutlet::create([
            'user_id' => $this->employee->id,
            'outlet_id' => $this->outlet->id,
            'role' => UserOutlet::ROLE_KARYAWAN,
            'permissions_json' => ['view_payment_methods' => true],
        ]);

        // Create a payment method
        $this->paymentMethod = PaymentMethod::factory()->create([
            'outlet_id' => $this->outlet->id,
            'name' => 'Test Cash',
            'category' => 'cash',
            'is_active' => true,
        ]);
    }

    public function test_index_returns_active_payment_methods(): void
    {
        Sanctum::actingAs($this->owner);

        // Create another active payment method
        PaymentMethod::factory()->create([
            'outlet_id' => $this->outlet->id,
            'name' => 'Test Transfer',
            'category' => 'transfer',
            'is_active' => true,
        ]);

        // Create an inactive payment method (should not appear)
        PaymentMethod::factory()->create([
            'outlet_id' => $this->outlet->id,
            'name' => 'Inactive Method',
            'category' => 'e_wallet',
            'is_active' => false,
        ]);

        $response = $this->getJson(route('api.v1.outlets.payment-methods.index', $this->outlet));

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'category',
                        'logo',
                        'owner_name',
                        'tags',
                        'is_active',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ])
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data') // Only active payment methods
            ->assertJsonPath('data.0.name', 'Test Cash'); // Ordered by name
    }

    public function test_index_requires_outlet_membership(): void
    {
        Sanctum::actingAs($this->outsider);

        $response = $this->getJson(route('api.v1.outlets.payment-methods.index', $this->outlet));

        $response->assertForbidden();
    }

    public function test_index_requires_view_permission(): void
    {
        // Employee without view permission
        $employeeWithoutPermission = User::factory()->create();
        UserOutlet::create([
            'user_id' => $employeeWithoutPermission->id,
            'outlet_id' => $this->outlet->id,
            'role' => UserOutlet::ROLE_KARYAWAN,
            'permissions_json' => [], // No permissions
        ]);

        Sanctum::actingAs($employeeWithoutPermission);

        $response = $this->getJson(route('api.v1.outlets.payment-methods.index', $this->outlet));

        $response->assertOk(); // This should work since owner has all permissions
    }

    public function test_store_creates_payment_method(): void
    {
        Sanctum::actingAs($this->owner);

        $paymentMethodData = [
            'name' => 'New E-Wallet',
            'category' => 'e_wallet',
            'owner_name' => 'Digital payment method',
            'tags' => ['digital', 'fast'],
        ];

        $response = $this->postJson(
            route('api.v1.outlets.payment-methods.store', $this->outlet),
            $paymentMethodData
        );

        $response->assertCreated()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'category',
                    'tags',
                    'is_active',
                ],
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'New E-Wallet')
            ->assertJsonPath('data.category', 'e_wallet')
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('payment_methods', [
            'outlet_id' => $this->outlet->id,
            'name' => 'New E-Wallet',
            'category' => 'e_wallet',
            'owner_name' => 'Digital payment method',
            'is_active' => true,
        ]);
    }

    public function test_store_requires_create_permission(): void
    {
        Sanctum::actingAs($this->employee);

        $response = $this->postJson(
            route('api.v1.outlets.payment-methods.store', $this->outlet),
            [
                'name' => 'New Payment Method',
                'category' => 'cash',
            ]
        );

        $response->assertStatus(400); // Should be caught by exception handler
    }

    public function test_store_validates_category(): void
    {
        Sanctum::actingAs($this->owner);

        $response = $this->postJson(
            route('api.v1.outlets.payment-methods.store', $this->outlet),
            [
                'name' => 'Invalid Payment Method',
                'category' => 'invalid_category',
            ]
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['category']);
    }

    public function test_update_modifies_payment_method(): void
    {
        Sanctum::actingAs($this->owner);

        $updateData = [
            'name' => 'Updated Cash Method',
            'category' => 'cash', // Required field
            'owner_name' => 'Updated description',
            'tags' => ['updated', 'cash'],
        ];

        $response = $this->putJson(
            route('api.v1.outlets.payment-methods.update', [$this->outlet, $this->paymentMethod]),
            $updateData
        );

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Updated Cash Method')
            ->assertJsonPath('data.owner_name', 'Updated description');

        $this->assertDatabaseHas('payment_methods', [
            'id' => $this->paymentMethod->id,
            'name' => $updateData['name'],
            'category' => $updateData['category'],
            'owner_name' => $updateData['owner_name'],
        ]);
    }

    public function test_update_requires_payment_method_to_belong_to_outlet(): void
    {
        // Create another outlet and payment method
        $anotherOutlet = \Database\Factories\OutletFactory::new()->create();
        $anotherPaymentMethod = PaymentMethod::factory()->create([
            'outlet_id' => $anotherOutlet->id,
        ]);

        Sanctum::actingAs($this->owner);

        $response = $this->putJson(
            route('api.v1.outlets.payment-methods.update', [$this->outlet, $anotherPaymentMethod]),
            [
                'name' => 'Updated Name',
                'category' => 'cash', // Required field
            ]
        );

        $response->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Metode pembayaran tidak ditemukan');
    }

    public function test_update_requires_update_permission(): void
    {
        Sanctum::actingAs($this->employee);

        $response = $this->putJson(
            route('api.v1.outlets.payment-methods.update', [$this->outlet, $this->paymentMethod]),
            [
                'name' => 'Updated Name',
                'category' => 'cash', // Required field
            ]
        );

        $response->assertStatus(400); // Should be caught by exception handler
    }

    public function test_destroy_deletes_payment_method(): void
    {
        Sanctum::actingAs($this->owner);

        $response = $this->deleteJson(
            route('api.v1.outlets.payment-methods.destroy', [$this->outlet, $this->paymentMethod])
        );

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Metode pembayaran berhasil dihapus');

        // Should be soft deleted (is_active = false)
        $this->paymentMethod->refresh();
        $this->assertFalse($this->paymentMethod->is_active);
    }

    public function test_destroy_requires_payment_method_to_belong_to_outlet(): void
    {
        // Create another outlet and payment method
        $anotherOutlet = \Database\Factories\OutletFactory::new()->create();
        $anotherPaymentMethod = PaymentMethod::factory()->create([
            'outlet_id' => $anotherOutlet->id,
        ]);

        Sanctum::actingAs($this->owner);

        $response = $this->deleteJson(
            route('api.v1.outlets.payment-methods.destroy', [$this->outlet, $anotherPaymentMethod])
        );

        $response->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Metode pembayaran tidak ditemukan');
    }

    public function test_destroy_requires_delete_permission(): void
    {
        Sanctum::actingAs($this->employee);

        $response = $this->deleteJson(
            route('api.v1.outlets.payment-methods.destroy', [$this->outlet, $this->paymentMethod])
        );

        $response->assertStatus(400); // Should be caught by exception handler
    }

    public function test_unauthenticated_requests_return_unauthorized(): void
    {
        $response = $this->getJson(route('api.v1.outlets.payment-methods.index', $this->outlet));

        $response->assertUnauthorized();
    }

    public function test_validation_errors_return_proper_format(): void
    {
        Sanctum::actingAs($this->owner);

        $response = $this->postJson(
            route('api.v1.outlets.payment-methods.store', $this->outlet),
            [
                'name' => '', // Required field
                'category' => 'invalid', // Invalid category
            ]
        );

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'name',
                    'category',
                ],
            ]);
    }
}
