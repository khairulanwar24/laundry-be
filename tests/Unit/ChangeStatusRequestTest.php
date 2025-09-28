<?php

namespace Tests\Unit;

use App\Http\Requests\Api\V1\Order\ChangeStatusRequest;
use App\Models\Order;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ChangeStatusRequestTest extends TestCase
{
    protected function getValidData(): array
    {
        return [
            'to' => Order::STATUS_PROSES,
            'notes' => 'Status change note',
        ];
    }

    protected function makeRequest(array $data = []): ChangeStatusRequest
    {
        $request = new ChangeStatusRequest;
        $request->replace($data);

        return $request;
    }

    public function test_authorize_returns_true(): void
    {
        $request = $this->makeRequest();

        $this->assertTrue($request->authorize());
    }

    public function test_valid_data_passes_validation(): void
    {
        $data = $this->getValidData();
        $request = $this->makeRequest($data);

        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertFalse($validator->fails());
    }

    public function test_to_status_is_required(): void
    {
        $data = $this->getValidData();
        unset($data['to']);

        $request = $this->makeRequest($data);
        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('to', $validator->errors()->toArray());
        $this->assertContains('Status tujuan wajib diisi.', $validator->errors()->get('to'));
    }

    public function test_to_status_must_be_string(): void
    {
        $data = $this->getValidData();
        $data['to'] = 123;

        $request = $this->makeRequest($data);
        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('to', $validator->errors()->toArray());
        $this->assertContains('Status tujuan harus berupa teks.', $validator->errors()->get('to'));
    }

    public function test_to_status_must_be_valid_status(): void
    {
        $data = $this->getValidData();
        $data['to'] = 'invalid_status';

        $request = $this->makeRequest($data);
        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('to', $validator->errors()->toArray());

        $errorMessage = $validator->errors()->get('to')[0];
        $this->assertStringContainsString('Status tujuan tidak valid', $errorMessage);
        $this->assertStringContainsString(Order::STATUS_ANTRIAN, $errorMessage);
        $this->assertStringContainsString(Order::STATUS_PROSES, $errorMessage);
        $this->assertStringContainsString(Order::STATUS_SIAP_DIAMBIL, $errorMessage);
        $this->assertStringContainsString(Order::STATUS_SELESAI, $errorMessage);
        $this->assertStringContainsString(Order::STATUS_BATAL, $errorMessage);
    }

    public function test_all_valid_statuses_are_accepted(): void
    {
        $validStatuses = [
            Order::STATUS_ANTRIAN,
            Order::STATUS_PROSES,
            Order::STATUS_SIAP_DIAMBIL,
            Order::STATUS_SELESAI,
            Order::STATUS_BATAL,
        ];

        foreach ($validStatuses as $status) {
            $data = $this->getValidData();
            $data['to'] = $status;

            $request = $this->makeRequest($data);
            $validator = Validator::make($data, $request->rules(), $request->messages());

            $this->assertFalse($validator->fails(), "Status '{$status}' should be valid");
        }
    }

    public function test_notes_is_optional(): void
    {
        $data = $this->getValidData();
        unset($data['notes']);

        $request = $this->makeRequest($data);
        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertFalse($validator->fails());
    }

    public function test_notes_can_be_null(): void
    {
        $data = $this->getValidData();
        $data['notes'] = null;

        $request = $this->makeRequest($data);
        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertFalse($validator->fails());
    }

    public function test_notes_must_be_string(): void
    {
        $data = $this->getValidData();
        $data['notes'] = 123;

        $request = $this->makeRequest($data);
        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('notes', $validator->errors()->toArray());
        $this->assertContains('Catatan harus berupa teks.', $validator->errors()->get('notes'));
    }

    public function test_notes_maximum_length(): void
    {
        $data = $this->getValidData();
        $data['notes'] = str_repeat('a', 1001);

        $request = $this->makeRequest($data);
        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('notes', $validator->errors()->toArray());
        $this->assertContains('Catatan maksimal 1000 karakter.', $validator->errors()->get('notes'));
    }

    public function test_notes_exactly_at_maximum_length(): void
    {
        $data = $this->getValidData();
        $data['notes'] = str_repeat('a', 1000);

        $request = $this->makeRequest($data);
        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertFalse($validator->fails());
    }

    public function test_failed_validation_throws_http_response_exception(): void
    {
        $data = ['to' => null]; // Invalid data
        $request = $this->makeRequest($data);

        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->expectException(HttpResponseException::class);

        // Create a reflection to access the protected method
        $reflection = new \ReflectionClass($request);
        $method = $reflection->getMethod('failedValidation');
        $method->setAccessible(true);
        $method->invoke($request, $validator);
    }

    public function test_failed_validation_response_structure(): void
    {
        $data = ['to' => null]; // Invalid data
        $request = $this->makeRequest($data);

        $validator = Validator::make($data, $request->rules(), $request->messages());

        try {
            // Create a reflection to access the protected method
            $reflection = new \ReflectionClass($request);
            $method = $reflection->getMethod('failedValidation');
            $method->setAccessible(true);
            $method->invoke($request, $validator);
        } catch (HttpResponseException $e) {
            $response = $e->getResponse();
            $content = json_decode($response->getContent(), true);

            $this->assertEquals(422, $response->getStatusCode());
            $this->assertArrayHasKey('success', $content);
            $this->assertArrayHasKey('message', $content);
            $this->assertArrayHasKey('data', $content);
            $this->assertArrayHasKey('errors', $content);
            $this->assertArrayHasKey('meta', $content);
            $this->assertFalse($content['success']);
            $this->assertEquals('Validasi gagal', $content['message']);
            $this->assertNull($content['data']);
            $this->assertNull($content['meta']);
        }
    }

    public function test_empty_string_notes(): void
    {
        $data = $this->getValidData();
        $data['notes'] = '';

        $request = $this->makeRequest($data);
        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertFalse($validator->fails());
    }

    public function test_whitespace_only_notes(): void
    {
        $data = $this->getValidData();
        $data['notes'] = '   ';

        $request = $this->makeRequest($data);
        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertFalse($validator->fails());
    }

    public function test_minimal_valid_request(): void
    {
        $data = ['to' => Order::STATUS_ANTRIAN];

        $request = $this->makeRequest($data);
        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertFalse($validator->fails());
    }

    public function test_multiple_validation_errors(): void
    {
        $data = [
            'to' => 'invalid_status',
            'notes' => str_repeat('a', 1001),
        ];

        $request = $this->makeRequest($data);
        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('to', $validator->errors()->toArray());
        $this->assertArrayHasKey('notes', $validator->errors()->toArray());
    }

    public function test_case_sensitive_status(): void
    {
        $data = $this->getValidData();
        $data['to'] = strtolower(Order::STATUS_PROSES); // Convert to lowercase

        $request = $this->makeRequest($data);
        $validator = Validator::make($data, $request->rules(), $request->messages());

        // Should fail because status values are case-sensitive and must be uppercase
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('to', $validator->errors()->toArray());
    }
}
