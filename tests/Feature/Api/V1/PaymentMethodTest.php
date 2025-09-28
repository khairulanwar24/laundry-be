<?php

namespace Tests\Feature\Api\V1;

use App\Models\Outlet;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Models\UserOutlet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentMethodTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $karyawan;

    private User $outsider;

    private Outlet $outlet1;

    private Outlet $outlet2;

    /**
     * Get authorization headers for API requests.
     */
    private function getAuthHeaders(string $token): array
    {
        return [
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Create users
        $this->owner = User::factory()->create(['name' => 'Owner User']);
        $this->karyawan = User::factory()->create(['name' => 'Karyawan User']);
        $this->outsider = User::factory()->create(['name' => 'Outsider User']);

        // Create outlets
        $this->outlet1 = \Database\Factories\OutletFactory::new()->create(['name' => 'Outlet Alpha']);
        $this->outlet2 = \Database\Factories\OutletFactory::new()->create(['name' => 'Outlet Beta']);

        // Set up outlet memberships
        UserOutlet::create([
            'user_id' => $this->owner->id,
            'outlet_id' => $this->outlet1->id,
            'role' => UserOutlet::ROLE_OWNER,
            'permissions_json' => ['all' => true],
        ]);

        UserOutlet::create([
            'user_id' => $this->karyawan->id,
            'outlet_id' => $this->outlet1->id,
            'role' => UserOutlet::ROLE_KARYAWAN,
            'permissions_json' => ['create_order' => true, 'view_revenue' => false],
        ]);

        // Owner of outlet2 (for testing isolation)
        UserOutlet::create([
            'user_id' => $this->owner->id,
            'outlet_id' => $this->outlet2->id,
            'role' => UserOutlet::ROLE_OWNER,
            'permissions_json' => ['all' => true],
        ]);
    }

    public function test_owner_can_crud_payment_methods(): void
    {
        $token = $this->owner->createToken('test-token')->plainTextToken;

        // CREATE: Create a new payment method
        $createData = [
            'category' => 'transfer',
            'name' => 'Bank BCA',
            'owner_name' => 'PT Laundry Segar',
            'tags' => ['virtual_account', 'instant'],
        ];

        $createResponse = $this->postJson(
            "/api/v1/outlets/{$this->outlet1->id}/payment-methods",
            $createData,
            $this->getAuthHeaders($token)
        );

        $createResponse->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'outlet_id',
                    'category',
                    'name',
                    'logo',
                    'owner_name',
                    'tags',
                    'is_active',
                    'created_at',
                    'updated_at',
                ],
                'errors',
                'meta',
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Metode pembayaran berhasil dibuat',
                'data' => [
                    'category' => 'transfer',
                    'name' => 'Bank BCA',
                    'owner_name' => 'PT Laundry Segar',
                    'tags' => ['virtual_account', 'instant'],
                    'is_active' => true,
                ],
                'errors' => null,
                'meta' => null,
            ]);

        $paymentMethodId = $createResponse->json('data.id');

        // Verify in database
        $this->assertDatabaseHas('payment_methods', [
            'id' => $paymentMethodId,
            'outlet_id' => $this->outlet1->id,
            'category' => 'transfer',
            'name' => 'Bank BCA',
            'owner_name' => 'PT Laundry Segar',
            'is_active' => true,
        ]);

        // READ: List payment methods
        $listResponse = $this->getJson(
            "/api/v1/outlets/{$this->outlet1->id}/payment-methods",
            $this->getAuthHeaders($token)
        );

        $listResponse->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'outlet_id',
                        'category',
                        'name',
                        'logo',
                        'owner_name',
                        'tags',
                        'is_active',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'errors',
                'meta',
            ])
            ->assertJson([
                'success' => true,
                'message' => 'OK',
                'errors' => null,
                'meta' => null,
            ]);

        // Verify the created payment method is in the list
        $responseData = $listResponse->json('data');
        $this->assertCount(1, $responseData);
        $this->assertEquals('Bank BCA', $responseData[0]['name']);

        // UPDATE: Update the payment method
        $updateData = [
            'category' => 'transfer',
            'name' => 'Bank BCA Premium',
            'owner_name' => 'PT Laundry Segar Jaya',
            'tags' => ['premium', 'instant', 'virtual_account'],
        ];

        $updateResponse = $this->putJson(
            "/api/v1/outlets/{$this->outlet1->id}/payment-methods/{$paymentMethodId}",
            $updateData,
            $this->getAuthHeaders($token)
        );

        $updateResponse->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'category',
                    'name',
                    'owner_name',
                    'tags',
                    'is_active',
                ],
                'errors',
                'meta',
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Metode pembayaran berhasil diperbarui',
                'data' => [
                    'name' => 'Bank BCA Premium',
                    'owner_name' => 'PT Laundry Segar Jaya',
                    'tags' => ['premium', 'instant', 'virtual_account'],
                ],
                'errors' => null,
                'meta' => null,
            ]);

        // Verify update in database
        $this->assertDatabaseHas('payment_methods', [
            'id' => $paymentMethodId,
            'name' => 'Bank BCA Premium',
            'owner_name' => 'PT Laundry Segar Jaya',
        ]);

        // DELETE: Delete the payment method (soft delete)
        $deleteResponse = $this->deleteJson(
            "/api/v1/outlets/{$this->outlet1->id}/payment-methods/{$paymentMethodId}",
            [],
            $this->getAuthHeaders($token)
        );

        $deleteResponse->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data',
                'errors',
                'meta',
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Metode pembayaran berhasil dihapus',
                'data' => null,
                'errors' => null,
                'meta' => null,
            ]);

        // Verify soft delete (is_active = false)
        $this->assertDatabaseHas('payment_methods', [
            'id' => $paymentMethodId,
            'is_active' => false,
        ]);

        // Verify it doesn't appear in active list
        $listAfterDeleteResponse = $this->getJson(
            "/api/v1/outlets/{$this->outlet1->id}/payment-methods",
            $this->getAuthHeaders($token)
        );

        $listAfterDeleteResponse->assertStatus(200)
            ->assertJsonCount(0, 'data'); // Should be empty since the only method was deleted
    }

    public function test_karyawan_cannot_create_payment_methods(): void
    {
        $token = $this->karyawan->createToken('test-token')->plainTextToken;

        // Try to create payment method as karyawan
        $createData = [
            'category' => 'cash',
            'name' => 'Cash Payment',
        ];

        $response = $this->postJson(
            "/api/v1/outlets/{$this->outlet1->id}/payment-methods",
            $createData,
            $this->getAuthHeaders($token)
        );

        // Should return 400 due to authorization failure in controller
        $response->assertStatus(400)
            ->assertJsonStructure([
                'success',
                'message',
                'data',
                'errors',
                'meta',
            ])
            ->assertJson([
                'success' => false,
                'data' => null,
                'errors' => null,
                'meta' => null,
            ]);

        $this->assertStringContainsString('Gagal membuat metode pembayaran', $response->json('message'));

        // Verify no payment method was created
        $this->assertDatabaseMissing('payment_methods', [
            'outlet_id' => $this->outlet1->id,
            'name' => 'Cash Payment',
        ]);
    }

    public function test_karyawan_cannot_update_or_delete_payment_methods(): void
    {
        // Create payment method as owner first
        $ownerToken = $this->owner->createToken('owner-token')->plainTextToken;
        $paymentMethod = PaymentMethod::factory()->create([
            'outlet_id' => $this->outlet1->id,
            'category' => 'cash',
            'name' => 'Original Cash',
        ]);

        $karyawanToken = $this->karyawan->createToken('karyawan-token')->plainTextToken;

        // Try to update as karyawan
        $updateResponse = $this->putJson(
            "/api/v1/outlets/{$this->outlet1->id}/payment-methods/{$paymentMethod->id}",
            [
                'category' => 'cash',
                'name' => 'Updated Cash',
            ],
            $this->getAuthHeaders($karyawanToken)
        );

        $updateResponse->assertStatus(400)
            ->assertJson([
                'success' => false,
                'data' => null,
                'errors' => null,
                'meta' => null,
            ]);

        $this->assertStringContainsString('Gagal memperbarui metode pembayaran', $updateResponse->json('message'));

        // Try to delete as karyawan
        $deleteResponse = $this->deleteJson(
            "/api/v1/outlets/{$this->outlet1->id}/payment-methods/{$paymentMethod->id}",
            [],
            $this->getAuthHeaders($karyawanToken)
        );

        $deleteResponse->assertStatus(400)
            ->assertJson([
                'success' => false,
                'data' => null,
                'errors' => null,
                'meta' => null,
            ]);

        $this->assertStringContainsString('Gagal menghapus metode pembayaran', $deleteResponse->json('message'));

        // Verify payment method is unchanged
        $this->assertDatabaseHas('payment_methods', [
            'id' => $paymentMethod->id,
            'name' => 'Original Cash',
            'is_active' => true,
        ]);
    }

    public function test_list_only_methods_from_same_outlet(): void
    {
        $token = $this->owner->createToken('test-token')->plainTextToken;

        // Create payment methods for both outlets
        $payment1 = PaymentMethod::factory()->create([
            'outlet_id' => $this->outlet1->id,
            'category' => 'cash',
            'name' => 'Cash Outlet 1',
            'is_active' => true,
        ]);

        $payment2 = PaymentMethod::factory()->create([
            'outlet_id' => $this->outlet1->id,
            'category' => 'transfer',
            'name' => 'Transfer Outlet 1',
            'is_active' => true,
        ]);

        $payment3 = PaymentMethod::factory()->create([
            'outlet_id' => $this->outlet2->id,
            'category' => 'e_wallet',
            'name' => 'E-Wallet Outlet 2',
            'is_active' => true,
        ]);

        // Create inactive payment method for outlet1 (should not appear)
        $payment4 = PaymentMethod::factory()->create([
            'outlet_id' => $this->outlet1->id,
            'category' => 'cash',
            'name' => 'Inactive Cash',
            'is_active' => false,
        ]);

        // List payment methods for outlet1
        $response1 = $this->getJson(
            "/api/v1/outlets/{$this->outlet1->id}/payment-methods",
            $this->getAuthHeaders($token)
        );

        $response1->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'outlet_id',
                        'category',
                        'name',
                        'is_active',
                    ],
                ],
                'errors',
                'meta',
            ])
            ->assertJson([
                'success' => true,
                'message' => 'OK',
                'errors' => null,
                'meta' => null,
            ])
            ->assertJsonCount(2, 'data'); // Only 2 active payment methods for outlet1

        // Verify only outlet1 payment methods are returned
        $responseData1 = $response1->json('data');
        $names1 = collect($responseData1)->pluck('name')->toArray();
        $this->assertContains('Cash Outlet 1', $names1);
        $this->assertContains('Transfer Outlet 1', $names1);
        $this->assertNotContains('E-Wallet Outlet 2', $names1);
        $this->assertNotContains('Inactive Cash', $names1);

        // Verify all returned payment methods belong to outlet1
        foreach ($responseData1 as $paymentMethod) {
            $this->assertEquals($this->outlet1->id, $paymentMethod['outlet_id']);
            $this->assertTrue($paymentMethod['is_active']);
        }

        // List payment methods for outlet2
        $response2 = $this->getJson(
            "/api/v1/outlets/{$this->outlet2->id}/payment-methods",
            $this->getAuthHeaders($token)
        );

        $response2->assertStatus(200)
            ->assertJsonCount(1, 'data'); // Only 1 payment method for outlet2

        $responseData2 = $response2->json('data');
        $this->assertEquals('E-Wallet Outlet 2', $responseData2[0]['name']);
        $this->assertEquals($this->outlet2->id, $responseData2[0]['outlet_id']);
    }

    public function test_non_member_cannot_access_payment_methods(): void
    {
        $outsiderToken = $this->outsider->createToken('outsider-token')->plainTextToken;

        // Try to list payment methods for outlet1 (not a member)
        $listResponse = $this->getJson(
            "/api/v1/outlets/{$this->outlet1->id}/payment-methods",
            $this->getAuthHeaders($outsiderToken)
        );

        $listResponse->assertStatus(403)
            ->assertJsonStructure([
                'success',
                'message',
            ])
            ->assertJson([
                'success' => false,
                'message' => 'Anda tidak terdaftar di outlet ini',
            ]);

        // Try to create payment method
        $createResponse = $this->postJson(
            "/api/v1/outlets/{$this->outlet1->id}/payment-methods",
            [
                'category' => 'cash',
                'name' => 'Unauthorized Cash',
            ],
            $this->getAuthHeaders($outsiderToken)
        );

        $createResponse->assertStatus(403);
    }

    public function test_cannot_access_payment_method_from_different_outlet(): void
    {
        $token = $this->owner->createToken('test-token')->plainTextToken;

        // Create payment method in outlet2
        $paymentMethod = PaymentMethod::factory()->create([
            'outlet_id' => $this->outlet2->id,
            'category' => 'cash',
            'name' => 'Cash Outlet 2',
        ]);

        // Try to update payment method from outlet2 using outlet1's endpoint
        $updateResponse = $this->putJson(
            "/api/v1/outlets/{$this->outlet1->id}/payment-methods/{$paymentMethod->id}",
            [
                'category' => 'cash',
                'name' => 'Updated Name',
            ],
            $this->getAuthHeaders($token)
        );

        $updateResponse->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Metode pembayaran tidak ditemukan',
                'data' => null,
                'errors' => null,
                'meta' => null,
            ]);

        // Try to delete payment method from outlet2 using outlet1's endpoint
        $deleteResponse = $this->deleteJson(
            "/api/v1/outlets/{$this->outlet1->id}/payment-methods/{$paymentMethod->id}",
            [],
            $this->getAuthHeaders($token)
        );

        $deleteResponse->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Metode pembayaran tidak ditemukan',
                'data' => null,
                'errors' => null,
                'meta' => null,
            ]);
    }

    public function test_validation_errors_return_proper_envelope(): void
    {
        $token = $this->owner->createToken('test-token')->plainTextToken;

        // Try to create payment method with invalid data
        $response = $this->postJson(
            "/api/v1/outlets/{$this->outlet1->id}/payment-methods",
            [
                'category' => 'invalid_category',
                'name' => '', // Empty name
            ],
            $this->getAuthHeaders($token)
        );

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'data',
                'errors' => [
                    'category',
                    'name',
                ],
                'meta',
            ])
            ->assertJson([
                'success' => false,
                'message' => 'Validasi gagal',
                'data' => null,
                'meta' => null,
            ]);

        $this->assertIsArray($response->json('errors.category'));
        $this->assertIsArray($response->json('errors.name'));
    }

    public function test_unauthenticated_user_cannot_access_payment_methods(): void
    {
        // Try to list payment methods without authentication
        $response = $this->getJson("/api/v1/outlets/{$this->outlet1->id}/payment-methods");
        $response->assertStatus(401);

        // Try to create payment method without authentication
        $response = $this->postJson("/api/v1/outlets/{$this->outlet1->id}/payment-methods", [
            'category' => 'cash',
            'name' => 'Unauthorized Cash',
        ]);
        $response->assertStatus(401);
    }
}
