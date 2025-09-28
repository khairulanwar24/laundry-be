<?php

namespace Tests\Feature\Api\V1;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Outlet;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\ServiceVariant;
use App\Models\User;
use App\Models\UserOutlet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Outlet $outlet;

    private Customer $customer;

    private PaymentMethod $paymentMethod;

    private ServiceVariant $serviceVariantKg;

    private ServiceVariant $serviceVariantPcs;

    protected function setUp(): void
    {
        parent::setUp();

        // Create user and outlet
        $this->user = User::factory()->create();
        $this->outlet = Outlet::factory()->create(['owner_user_id' => $this->user->id]);

        // Create user-outlet relationship
        UserOutlet::factory()->create([
            'user_id' => $this->user->id,
            'outlet_id' => $this->outlet->id,
            'role' => UserOutlet::ROLE_OWNER,
        ]);

        // Create test data
        $this->customer = Customer::factory()->create(['outlet_id' => $this->outlet->id]);
        $this->paymentMethod = PaymentMethod::factory()->create(['outlet_id' => $this->outlet->id]);

        // Create service variants with different units
        $this->serviceVariantKg = ServiceVariant::factory()->create(['unit' => 'kg', 'price_per_unit' => 8000]);
        $this->serviceVariantPcs = ServiceVariant::factory()->create(['unit' => 'pcs', 'price_per_unit' => 5000]);

        // Authenticate user
        Sanctum::actingAs($this->user);
    }

    public function test_can_create_order_and_generate_invoice_format_j_l_yymmddnnnn(): void
    {
        $orderData = [
            'customer_id' => $this->customer->id,
            'items' => [
                [
                    'service_variant_id' => $this->serviceVariantKg->id,
                    'qty' => 2.5,
                    'note' => 'Test laundry',
                ],
            ],
            'notes' => 'Test order',
        ];

        $response = $this->postJson("/api/v1/outlets/{$this->outlet->id}/orders", $orderData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'invoice_no',
                    'status',
                    'items' => [
                        '*' => [
                            'id',
                            'service_variant_id',
                            'qty',
                            'price_per_unit_snapshot',
                            'line_total',
                        ],
                    ],
                ],
                'errors',
                'meta',
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Pesanan berhasil dibuat',
            ]);

        // Check invoice format JL-YYMMDDNNNN
        $invoiceNo = $response->json('data.invoice_no');
        $today = now()->format('ymd');
        $this->assertStringStartsWith("JL-{$today}", $invoiceNo);
        $this->assertMatchesRegularExpression('/^JL-\d{6}\d{4}$/', $invoiceNo);

        // Verify database
        $this->assertDatabaseHas('orders', [
            'outlet_id' => $this->outlet->id,
            'customer_id' => $this->customer->id,
            'invoice_no' => $invoiceNo,
            'status' => Order::STATUS_ANTRIAN,
        ]);
    }

    public function test_cannot_create_order_with_invalid_qty_for_pcs_integer_only(): void
    {
        $orderData = [
            'customer_id' => $this->customer->id,
            'items' => [
                [
                    'service_variant_id' => $this->serviceVariantPcs->id,
                    'qty' => 2.5, // Invalid for pcs - should be integer
                    'note' => 'Test',
                ],
            ],
        ];

        $response = $this->postJson("/api/v1/outlets/{$this->outlet->id}/orders", $orderData);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'data',
                'errors',
                'meta',
            ])
            ->assertJson([
                'success' => false,
            ]);

        // Should not create order in database
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_can_change_status_through_valid_transitions_and_logs_history(): void
    {
        // Create an order
        $order = Order::factory()->create([
            'outlet_id' => $this->outlet->id,
            'status' => Order::STATUS_ANTRIAN,
        ]);

        // Test status transition: ANTRIAN -> PROSES  (FIXED: use 'to' instead of 'status')
        $response = $this->postJson("/api/v1/outlets/{$this->outlet->id}/orders/{$order->id}/status", [
            'to' => Order::STATUS_PROSES,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'status',
                ],
                'errors',
                'meta',
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Status pesanan berhasil diubah',
                'data' => [
                    'status' => Order::STATUS_PROSES,
                ],
            ]);

        // Verify database update
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => Order::STATUS_PROSES,
        ]);

        // Verify status history is logged
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'to_status' => Order::STATUS_PROSES,
        ]);
    }

    public function test_can_pay_order_single_method_and_updates_payment_status(): void
    {
        $order = Order::factory()->create([
            'outlet_id' => $this->outlet->id,
            'total' => 50000,
            'payment_status' => Order::PAYMENT_STATUS_UNPAID,
        ]);

        $paymentData = [
            'payment_method_id' => $this->paymentMethod->id,
            'amount' => 50000,
            'ref_no' => 'PAY123456',
        ];

        $response = $this->postJson("/api/v1/outlets/{$this->outlet->id}/orders/{$order->id}/pay", $paymentData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'payment_status',
                ],
                'errors',
                'meta',
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Pembayaran berhasil diproses',
            ]);

        // Verify order payment status updated
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'payment_status' => Order::PAYMENT_STATUS_PAID,
        ]);

        // Verify payment record created
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'method_id' => $this->paymentMethod->id,
            'amount' => 50000,
            'ref_no' => 'PAY123456',
            'status' => Payment::STATUS_SUCCESS,
        ]);
    }

    public function test_cannot_pay_if_amount_not_equal_total(): void
    {
        $order = Order::factory()->create([
            'outlet_id' => $this->outlet->id,
            'total' => 50000,
            'payment_status' => Order::PAYMENT_STATUS_UNPAID,
        ]);

        $paymentData = [
            'payment_method_id' => $this->paymentMethod->id,
            'amount' => 40000, // Less than order total
            'ref_no' => 'PAY123456',
        ];

        $response = $this->postJson("/api/v1/outlets/{$this->outlet->id}/orders/{$order->id}/pay", $paymentData);

        $response->assertStatus(500) // Service should throw exception
            ->assertJsonStructure([
                'success',
                'message',
                'data',
                'errors',
                'meta',
            ])
            ->assertJson([
                'success' => false,
                'message' => 'Gagal memproses pembayaran',
            ]);

        // Verify order payment status not updated
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'payment_status' => Order::PAYMENT_STATUS_UNPAID,
        ]);

        // Verify no payment record created
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_can_pickup_only_when_status_selesai(): void
    {
        // Test with order in SIAP_DIAMBIL status (ready for pickup)
        $orderSiapDiambil = Order::factory()->create([
            'outlet_id' => $this->outlet->id,
            'status' => Order::STATUS_SIAP_DIAMBIL,
            'collected_at' => null,
        ]);

        $response = $this->postJson("/api/v1/outlets/{$this->outlet->id}/orders/{$orderSiapDiambil->id}/pickup");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'collected_at',
                ],
                'errors',
                'meta',
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Pesanan berhasil ditandai sebagai diambil',
            ]);

        // Verify collected_at is set and status is updated to SELESAI
        $orderSiapDiambil->refresh();
        $this->assertNotNull($orderSiapDiambil->collected_at);
        $this->assertEquals(Order::STATUS_SELESAI, $orderSiapDiambil->status);

        // Test with order NOT in SELESAI status (should fail)
        $orderProses = Order::factory()->create([
            'outlet_id' => $this->outlet->id,
            'status' => Order::STATUS_PROSES,
        ]);

        $response = $this->postJson("/api/v1/outlets/{$this->outlet->id}/orders/{$orderProses->id}/pickup");

        $response->assertStatus(500) // Service should throw exception
            ->assertJsonStructure([
                'success',
                'message',
                'data',
                'errors',
                'meta',
            ])
            ->assertJson([
                'success' => false,
                'message' => 'Gagal menandai pesanan sebagai diambil',
            ]);
    }

    public function test_tabs_filter_work_as_expected(): void
    {
        // Create orders with different statuses
        $orderAntrian = Order::factory()->create([
            'outlet_id' => $this->outlet->id,
            'status' => Order::STATUS_ANTRIAN,
        ]);

        $orderProses = Order::factory()->create([
            'outlet_id' => $this->outlet->id,
            'status' => Order::STATUS_PROSES,
        ]);

        $orderSiapAmbil = Order::factory()->create([
            'outlet_id' => $this->outlet->id,
            'status' => Order::STATUS_SIAP_DIAMBIL,
        ]);

        $orderSelesai = Order::factory()->create([
            'outlet_id' => $this->outlet->id,
            'status' => Order::STATUS_SELESAI,
        ]);

        $orderBatal = Order::factory()->create([
            'outlet_id' => $this->outlet->id,
            'status' => Order::STATUS_BATAL,
        ]);

        // Test tab=antrian
        $response = $this->getJson("/api/v1/outlets/{$this->outlet->id}/orders?tab=antrian");
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Daftar pesanan berhasil diambil',
            ]);

        $orderIds = collect($response->json('data.data'))->pluck('id')->toArray();
        $this->assertContains($orderAntrian->id, $orderIds);
        $this->assertNotContains($orderProses->id, $orderIds);

        // Test tab=proses
        $response = $this->getJson("/api/v1/outlets/{$this->outlet->id}/orders?tab=proses");
        $response->assertStatus(200);

        $orderIds = collect($response->json('data.data'))->pluck('id')->toArray();
        $this->assertContains($orderProses->id, $orderIds);
        $this->assertNotContains($orderAntrian->id, $orderIds);

        // Test tab=siap-ambil
        $response = $this->getJson("/api/v1/outlets/{$this->outlet->id}/orders?tab=siap-ambil");
        $response->assertStatus(200);

        $orderIds = collect($response->json('data.data'))->pluck('id')->toArray();
        $this->assertContains($orderSiapAmbil->id, $orderIds);
        $this->assertNotContains($orderSelesai->id, $orderIds);

        // Test tab=selesai
        $response = $this->getJson("/api/v1/outlets/{$this->outlet->id}/orders?tab=selesai");
        $response->assertStatus(200);

        $orderIds = collect($response->json('data.data'))->pluck('id')->toArray();
        $this->assertContains($orderSelesai->id, $orderIds);
        $this->assertNotContains($orderBatal->id, $orderIds);

        // Test tab=batal
        $response = $this->getJson("/api/v1/outlets/{$this->outlet->id}/orders?tab=batal");
        $response->assertStatus(200);

        $orderIds = collect($response->json('data.data'))->pluck('id')->toArray();
        $this->assertContains($orderBatal->id, $orderIds);
        $this->assertNotContains($orderSelesai->id, $orderIds);

        // Test search functionality
        $response = $this->getJson("/api/v1/outlets/{$this->outlet->id}/orders?q={$orderAntrian->invoice_no}");
        $response->assertStatus(200);

        $orderIds = collect($response->json('data.data'))->pluck('id')->toArray();
        $this->assertContains($orderAntrian->id, $orderIds);
        $this->assertCount(1, $orderIds);
    }

    public function test_outlet_scoping_prevents_cross_outlet_access(): void
    {
        // Create another outlet and order
        $otherOutlet = Outlet::factory()->create();
        $otherOrder = Order::factory()->create(['outlet_id' => $otherOutlet->id]);

        // Try to access order from different outlet
        $response = $this->getJson("/api/v1/outlets/{$this->outlet->id}/orders/{$otherOrder->id}");

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Pesanan tidak ditemukan',
            ]);

        // Try to change status of order from different outlet
        $response = $this->postJson("/api/v1/outlets/{$this->outlet->id}/orders/{$otherOrder->id}/status", [
            'to' => Order::STATUS_PROSES,
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Pesanan tidak ditemukan',
            ]);

        // Try to pay for order from different outlet
        $response = $this->postJson("/api/v1/outlets/{$this->outlet->id}/orders/{$otherOrder->id}/pay", [
            'payment_method_id' => $this->paymentMethod->id,
            'amount' => 50000,
            'ref_no' => 'PAY123456',
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Pesanan tidak ditemukan',
            ]);

        // Try to pickup order from different outlet
        $response = $this->postJson("/api/v1/outlets/{$this->outlet->id}/orders/{$otherOrder->id}/pickup");

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Pesanan tidak ditemukan',
            ]);
    }
}
