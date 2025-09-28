<?php

namespace Tests\Unit;

use App\Models\PaymentMethod;
use App\Services\Api\V1\PaymentMethodService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentMethodServiceTest extends TestCase
{
    use RefreshDatabase;

    private PaymentMethodService $paymentMethodService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->paymentMethodService = new PaymentMethodService;
    }

    public function test_create_payment_method_successfully(): void
    {
        $outlet = \Database\Factories\OutletFactory::new()->create(['is_active' => true]);
        $data = [
            'category' => PaymentMethod::CATEGORY_CASH,
            'name' => 'Tunai',
            'tags' => ['populer', 'cepat'],
        ];

        $paymentMethod = $this->paymentMethodService->create($outlet, $data);

        $this->assertInstanceOf(PaymentMethod::class, $paymentMethod);
        $this->assertEquals($outlet->id, $paymentMethod->outlet_id);
        $this->assertEquals(PaymentMethod::CATEGORY_CASH, $paymentMethod->category);
        $this->assertEquals('Tunai', $paymentMethod->name);
        $this->assertEquals(['populer', 'cepat'], $paymentMethod->tags);
        $this->assertTrue($paymentMethod->is_active);

        $this->assertDatabaseHas('payment_methods', [
            'outlet_id' => $outlet->id,
            'category' => PaymentMethod::CATEGORY_CASH,
            'name' => 'Tunai',
            'is_active' => true,
        ]);
    }

    public function test_create_transfer_payment_method_with_owner_name(): void
    {
        $outlet = \Database\Factories\OutletFactory::new()->create(['is_active' => true]);
        $data = [
            'category' => PaymentMethod::CATEGORY_TRANSFER,
            'name' => 'BCA',
            'owner_name' => 'John Doe',
            'tags' => ['transfer', 'bank'],
            'is_active' => false,
        ];

        $paymentMethod = $this->paymentMethodService->create($outlet, $data);

        $this->assertEquals(PaymentMethod::CATEGORY_TRANSFER, $paymentMethod->category);
        $this->assertEquals('BCA', $paymentMethod->name);
        $this->assertEquals('John Doe', $paymentMethod->owner_name);
        $this->assertEquals(['transfer', 'bank'], $paymentMethod->tags);
        $this->assertFalse($paymentMethod->is_active);
    }

    public function test_create_e_wallet_payment_method(): void
    {
        $outlet = \Database\Factories\OutletFactory::new()->create(['is_active' => true]);
        $data = [
            'category' => PaymentMethod::CATEGORY_E_WALLET,
            'name' => 'GoPay',
        ];

        $paymentMethod = $this->paymentMethodService->create($outlet, $data);

        $this->assertEquals(PaymentMethod::CATEGORY_E_WALLET, $paymentMethod->category);
        $this->assertEquals('GoPay', $paymentMethod->name);
        $this->assertNull($paymentMethod->owner_name);
        $this->assertNull($paymentMethod->tags);
    }

    public function test_create_throws_exception_for_inactive_outlet(): void
    {
        $outlet = \Database\Factories\OutletFactory::new()->create(['is_active' => false]);
        $data = [
            'category' => PaymentMethod::CATEGORY_CASH,
            'name' => 'Tunai',
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Cannot create payment method for inactive outlet');

        $this->paymentMethodService->create($outlet, $data);
    }

    public function test_create_throws_exception_for_invalid_category(): void
    {
        $outlet = \Database\Factories\OutletFactory::new()->create(['is_active' => true]);
        $data = [
            'category' => 'invalid_category',
            'name' => 'Test',
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid payment method category');

        $this->paymentMethodService->create($outlet, $data);
    }

    public function test_create_handles_non_array_tags(): void
    {
        $outlet = \Database\Factories\OutletFactory::new()->create(['is_active' => true]);
        $data = [
            'category' => PaymentMethod::CATEGORY_CASH,
            'name' => 'Tunai',
            'tags' => 'not_an_array',
        ];

        $paymentMethod = $this->paymentMethodService->create($outlet, $data);

        $this->assertEquals([], $paymentMethod->tags);
    }

    public function test_update_payment_method_successfully(): void
    {
        $paymentMethod = PaymentMethod::factory()->create([
            'category' => PaymentMethod::CATEGORY_CASH,
            'name' => 'Old Name',
            'tags' => ['old'],
        ]);

        $updateData = [
            'name' => 'New Name',
            'tags' => ['new', 'updated'],
            'is_active' => false,
        ];

        $updatedPaymentMethod = $this->paymentMethodService->update($paymentMethod, $updateData);

        $this->assertEquals('New Name', $updatedPaymentMethod->name);
        $this->assertEquals(['new', 'updated'], $updatedPaymentMethod->tags);
        $this->assertFalse($updatedPaymentMethod->is_active);
        $this->assertEquals(PaymentMethod::CATEGORY_CASH, $updatedPaymentMethod->category); // Unchanged
    }

    public function test_update_payment_method_category(): void
    {
        $paymentMethod = PaymentMethod::factory()->create([
            'category' => PaymentMethod::CATEGORY_CASH,
        ]);

        $updateData = [
            'category' => PaymentMethod::CATEGORY_TRANSFER,
            'owner_name' => 'New Owner',
        ];

        $updatedPaymentMethod = $this->paymentMethodService->update($paymentMethod, $updateData);

        $this->assertEquals(PaymentMethod::CATEGORY_TRANSFER, $updatedPaymentMethod->category);
        $this->assertEquals('New Owner', $updatedPaymentMethod->owner_name);
    }

    public function test_update_throws_exception_for_invalid_category(): void
    {
        $paymentMethod = PaymentMethod::factory()->create();
        $updateData = ['category' => 'invalid_category'];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid payment method category');

        $this->paymentMethodService->update($paymentMethod, $updateData);
    }

    public function test_update_handles_non_array_tags(): void
    {
        $paymentMethod = PaymentMethod::factory()->create(['tags' => ['original']]);
        $updateData = ['tags' => 'not_an_array'];

        $updatedPaymentMethod = $this->paymentMethodService->update($paymentMethod, $updateData);

        $this->assertEquals([], $updatedPaymentMethod->tags);
    }

    public function test_delete_payment_method_successfully(): void
    {
        $paymentMethod = PaymentMethod::factory()->create(['is_active' => true]);

        $this->paymentMethodService->delete($paymentMethod);

        $paymentMethod->refresh();
        $this->assertFalse($paymentMethod->is_active);
    }

    public function test_delete_handles_already_deleted_payment_method(): void
    {
        // Mock the payment method to return false on update
        $paymentMethodMock = \Mockery::mock(PaymentMethod::class);
        $paymentMethodMock->shouldReceive('update')->with(['is_active' => false])->andReturn(false);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Failed to delete payment method');

        $this->paymentMethodService->delete($paymentMethodMock);
    }
}
