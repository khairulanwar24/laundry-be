<?php

namespace Tests\Feature;

use App\Http\Requests\Api\V1\Outlet\InviteEmployeeRequest;
use App\Http\Requests\Api\V1\Outlet\StoreOutletRequest;
use App\Http\Requests\Api\V1\PaymentMethod\StorePaymentMethodRequest;
use App\Http\Requests\Api\V1\PaymentMethod\UpdatePaymentMethodRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class FormRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_outlet_request_validation_rules(): void
    {
        $request = new StoreOutletRequest;
        $rules = $request->rules();

        // Test valid data
        $validData = [
            'name' => 'Test Outlet',
            'address' => 'Jl. Test No. 123',
            'phone' => '081234567890',
            'logo_path' => 'https://example.com/logo.png',
        ];

        $validator = Validator::make($validData, $rules);
        $this->assertTrue($validator->passes());

        // Test invalid data
        $invalidData = [
            'name' => '', // required
            'address' => str_repeat('a', 256), // max 255
            'phone' => str_repeat('1', 31), // max 30
            'logo_path' => 'invalid-url', // must be URL
        ];

        $validator = Validator::make($invalidData, $rules);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
        $this->assertArrayHasKey('address', $validator->errors()->toArray());
        $this->assertArrayHasKey('phone', $validator->errors()->toArray());
        $this->assertArrayHasKey('logo_path', $validator->errors()->toArray());
    }

    public function test_invite_employee_request_validation_rules(): void
    {
        $request = new InviteEmployeeRequest;
        $rules = $request->rules();

        // Test valid data
        $validData = [
            'role' => 'karyawan',
            'email' => 'test@example.com',
            'phone' => '081234567890',
            'name' => 'Test Employee',
            'permissions' => ['CREATE_ORDER' => true, 'CANCEL_ORDER' => false],
        ];

        $validator = Validator::make($validData, $rules);
        $this->assertTrue($validator->passes());

        // Test invalid role
        $invalidData = [
            'role' => 'invalid_role',
            'email' => 'test@example.com',
        ];

        $validator = Validator::make($invalidData, $rules);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('role', $validator->errors()->toArray());
    }

    public function test_invite_employee_request_custom_validation(): void
    {
        // Test that either email or phone is required
        $request = new InviteEmployeeRequest;

        // Create a validator manually to test the custom validation
        $data = [
            'role' => 'karyawan',
            'name' => 'Test Employee',
            // No email or phone
        ];

        $validator = Validator::make($data, $request->rules());
        $request->withValidator($validator);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('contact', $validator->errors()->toArray());
    }

    public function test_store_payment_method_request_validation_rules(): void
    {
        $request = new StorePaymentMethodRequest;
        $rules = $request->rules();

        // Test valid data
        $validData = [
            'category' => 'cash',
            'name' => 'Tunai',
            'owner_name' => 'John Doe',
            'tags' => ['populer', 'cepat'],
            'is_active' => true,
        ];

        $validator = Validator::make($validData, $rules);
        $this->assertTrue($validator->passes());

        // Test invalid category
        $invalidData = [
            'category' => 'invalid_category',
            'name' => 'Test Payment',
        ];

        $validator = Validator::make($invalidData, $rules);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('category', $validator->errors()->toArray());

        // Test invalid tags
        $invalidTagsData = [
            'category' => 'cash',
            'name' => 'Test Payment',
            'tags' => ['valid_tag', str_repeat('a', 31)], // one tag too long
        ];

        $validator = Validator::make($invalidTagsData, $rules);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('tags.1', $validator->errors()->toArray());
    }

    public function test_update_payment_method_request_validation_rules(): void
    {
        $request = new UpdatePaymentMethodRequest;
        $rules = $request->rules();

        // Test valid data
        $validData = [
            'category' => 'e_wallet',
            'name' => 'GoPay',
            'tags' => ['mudah'],
            'is_active' => false,
        ];

        $validator = Validator::make($validData, $rules);
        $this->assertTrue($validator->passes());

        // Test missing required fields
        $invalidData = [
            'owner_name' => 'John Doe',
            // Missing category and name
        ];

        $validator = Validator::make($invalidData, $rules);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('category', $validator->errors()->toArray());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    public function test_form_requests_have_correct_messages(): void
    {
        $storeOutletRequest = new StoreOutletRequest;
        $messages = $storeOutletRequest->messages();

        $this->assertArrayHasKey('name.required', $messages);
        $this->assertStringContainsString('Nama outlet wajib diisi', $messages['name.required']);

        $inviteEmployeeRequest = new InviteEmployeeRequest;
        $messages = $inviteEmployeeRequest->messages();

        $this->assertArrayHasKey('role.required', $messages);
        $this->assertStringContainsString('Role wajib diisi', $messages['role.required']);

        $storePaymentRequest = new StorePaymentMethodRequest;
        $messages = $storePaymentRequest->messages();

        $this->assertArrayHasKey('category.required', $messages);
        $this->assertStringContainsString('Kategori wajib diisi', $messages['category.required']);
    }
}
