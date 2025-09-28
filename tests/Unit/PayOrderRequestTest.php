<?php

namespace Tests\Unit;

use App\Http\Requests\Api\V1\Order\PayOrderRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class PayOrderRequestTest extends TestCase
{
    protected function getValidData(): array
    {
        return [
            'payment_method_id' => 1,
            'amount' => 150000.50,
            'ref_no' => 'REF123456789',
        ];
    }

    protected function makeRequest(array $data = []): PayOrderRequest
    {
        $request = new PayOrderRequest;
        $request->replace($data);

        return $request;
    }

    protected function getRulesWithoutDatabaseValidation(): array
    {
        return [
            'payment_method_id' => ['required', 'integer'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'ref_no' => ['nullable', 'string', 'max:255'],
        ];
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

        $validator = Validator::make($data, $this->getRulesWithoutDatabaseValidation(), $request->messages());

        $this->assertFalse($validator->fails());
    }

    public function test_payment_method_id_is_required(): void
    {
        $data = $this->getValidData();
        unset($data['payment_method_id']);

        $request = $this->makeRequest($data);
        $validator = Validator::make($data, $this->getRulesWithoutDatabaseValidation(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('payment_method_id', $validator->errors()->toArray());
        $this->assertContains('ID metode pembayaran wajib diisi.', $validator->errors()->get('payment_method_id'));
    }

    public function test_payment_method_id_must_be_integer(): void
    {
        $data = $this->getValidData();
        $data['payment_method_id'] = 'not-a-number';

        $request = $this->makeRequest($data);
        $validator = Validator::make($data, $this->getRulesWithoutDatabaseValidation(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('payment_method_id', $validator->errors()->toArray());
        $this->assertContains('ID metode pembayaran harus berupa angka.', $validator->errors()->get('payment_method_id'));
    }

    public function test_payment_method_id_accepts_numeric_string(): void
    {
        $data = $this->getValidData();
        $data['payment_method_id'] = '1';

        $request = $this->makeRequest($data);
        $validator = Validator::make($data, $this->getRulesWithoutDatabaseValidation(), $request->messages());

        $this->assertFalse($validator->fails());
    }

    public function test_amount_is_required(): void
    {
        $data = $this->getValidData();
        unset($data['amount']);

        $request = $this->makeRequest($data);
        $validator = Validator::make($data, $this->getRulesWithoutDatabaseValidation(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('amount', $validator->errors()->toArray());
        $this->assertContains('Jumlah pembayaran wajib diisi.', $validator->errors()->get('amount'));
    }

    public function test_amount_must_be_numeric(): void
    {
        $data = $this->getValidData();
        $data['amount'] = 'not-a-number';

        $request = $this->makeRequest($data);
        $validator = Validator::make($data, $this->getRulesWithoutDatabaseValidation(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('amount', $validator->errors()->toArray());
        $this->assertContains('Jumlah pembayaran harus berupa angka.', $validator->errors()->get('amount'));
    }

    public function test_amount_minimum_value(): void
    {
        $data = $this->getValidData();
        $data['amount'] = 0;

        $request = $this->makeRequest($data);
        $validator = Validator::make($data, $this->getRulesWithoutDatabaseValidation(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('amount', $validator->errors()->toArray());
        $this->assertContains('Jumlah pembayaran minimal 0.01.', $validator->errors()->get('amount'));
    }

    public function test_amount_exactly_minimum_value(): void
    {
        $data = $this->getValidData();
        $data['amount'] = 0.01;

        $request = $this->makeRequest($data);
        $validator = Validator::make($data, $this->getRulesWithoutDatabaseValidation(), $request->messages());

        $this->assertFalse($validator->fails());
    }

    public function test_amount_accepts_integer(): void
    {
        $data = $this->getValidData();
        $data['amount'] = 100000;

        $request = $this->makeRequest($data);
        $validator = Validator::make($data, $this->getRulesWithoutDatabaseValidation(), $request->messages());

        $this->assertFalse($validator->fails());
    }

    public function test_amount_accepts_decimal(): void
    {
        $data = $this->getValidData();
        $data['amount'] = 150000.75;

        $request = $this->makeRequest($data);
        $validator = Validator::make($data, $this->getRulesWithoutDatabaseValidation(), $request->messages());

        $this->assertFalse($validator->fails());
    }

    public function test_amount_accepts_string_numeric(): void
    {
        $data = $this->getValidData();
        $data['amount'] = '150000.50';

        $request = $this->makeRequest($data);
        $validator = Validator::make($data, $this->getRulesWithoutDatabaseValidation(), $request->messages());

        $this->assertFalse($validator->fails());
    }

    public function test_amount_negative_value_fails(): void
    {
        $data = $this->getValidData();
        $data['amount'] = -100;

        $request = $this->makeRequest($data);
        $validator = Validator::make($data, $this->getRulesWithoutDatabaseValidation(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('amount', $validator->errors()->toArray());
        $this->assertContains('Jumlah pembayaran minimal 0.01.', $validator->errors()->get('amount'));
    }

    public function test_ref_no_is_optional(): void
    {
        $data = $this->getValidData();
        unset($data['ref_no']);

        $request = $this->makeRequest($data);
        $validator = Validator::make($data, $this->getRulesWithoutDatabaseValidation(), $request->messages());

        $this->assertFalse($validator->fails());
    }

    public function test_ref_no_can_be_null(): void
    {
        $data = $this->getValidData();
        $data['ref_no'] = null;

        $request = $this->makeRequest($data);
        $validator = Validator::make($data, $this->getRulesWithoutDatabaseValidation(), $request->messages());

        $this->assertFalse($validator->fails());
    }

    public function test_ref_no_must_be_string(): void
    {
        $data = $this->getValidData();
        $data['ref_no'] = 123456;

        $request = $this->makeRequest($data);
        $validator = Validator::make($data, $this->getRulesWithoutDatabaseValidation(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('ref_no', $validator->errors()->toArray());
        $this->assertContains('Nomor referensi harus berupa teks.', $validator->errors()->get('ref_no'));
    }

    public function test_ref_no_maximum_length(): void
    {
        $data = $this->getValidData();
        $data['ref_no'] = str_repeat('a', 256);

        $request = $this->makeRequest($data);
        $validator = Validator::make($data, $this->getRulesWithoutDatabaseValidation(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('ref_no', $validator->errors()->toArray());
        $this->assertContains('Nomor referensi maksimal 255 karakter.', $validator->errors()->get('ref_no'));
    }

    public function test_ref_no_exactly_at_maximum_length(): void
    {
        $data = $this->getValidData();
        $data['ref_no'] = str_repeat('a', 255);

        $request = $this->makeRequest($data);
        $validator = Validator::make($data, $this->getRulesWithoutDatabaseValidation(), $request->messages());

        $this->assertFalse($validator->fails());
    }

    public function test_ref_no_empty_string(): void
    {
        $data = $this->getValidData();
        $data['ref_no'] = '';

        $request = $this->makeRequest($data);
        $validator = Validator::make($data, $this->getRulesWithoutDatabaseValidation(), $request->messages());

        $this->assertFalse($validator->fails());
    }

    public function test_ref_no_whitespace_only(): void
    {
        $data = $this->getValidData();
        $data['ref_no'] = '   ';

        $request = $this->makeRequest($data);
        $validator = Validator::make($data, $this->getRulesWithoutDatabaseValidation(), $request->messages());

        $this->assertFalse($validator->fails());
    }

    public function test_failed_validation_throws_http_response_exception(): void
    {
        $data = ['payment_method_id' => null]; // Invalid data
        $request = $this->makeRequest($data);

        $validator = Validator::make($data, $this->getRulesWithoutDatabaseValidation(), $request->messages());

        $this->expectException(HttpResponseException::class);

        // Create a reflection to access the protected method
        $reflection = new \ReflectionClass($request);
        $method = $reflection->getMethod('failedValidation');
        $method->setAccessible(true);
        $method->invoke($request, $validator);
    }

    public function test_failed_validation_response_structure(): void
    {
        $data = ['payment_method_id' => null]; // Invalid data
        $request = $this->makeRequest($data);

        $validator = Validator::make($data, $this->getRulesWithoutDatabaseValidation(), $request->messages());

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

    public function test_minimal_valid_request(): void
    {
        $data = [
            'payment_method_id' => 1,
            'amount' => 0.01,
        ];

        $request = $this->makeRequest($data);
        $validator = Validator::make($data, $this->getRulesWithoutDatabaseValidation(), $request->messages());

        $this->assertFalse($validator->fails());
    }

    public function test_multiple_validation_errors(): void
    {
        $data = [
            'payment_method_id' => 'not-a-number',
            'amount' => 'not-a-number',
            'ref_no' => str_repeat('a', 256),
        ];

        $request = $this->makeRequest($data);
        $validator = Validator::make($data, $this->getRulesWithoutDatabaseValidation(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('payment_method_id', $validator->errors()->toArray());
        $this->assertArrayHasKey('amount', $validator->errors()->toArray());
        $this->assertArrayHasKey('ref_no', $validator->errors()->toArray());
    }

    public function test_large_amount_value(): void
    {
        $data = $this->getValidData();
        $data['amount'] = 999999999.99;

        $request = $this->makeRequest($data);
        $validator = Validator::make($data, $this->getRulesWithoutDatabaseValidation(), $request->messages());

        $this->assertFalse($validator->fails());
    }

    public function test_very_small_decimal_amount(): void
    {
        $data = $this->getValidData();
        $data['amount'] = 0.001;

        $request = $this->makeRequest($data);
        $validator = Validator::make($data, $this->getRulesWithoutDatabaseValidation(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('amount', $validator->errors()->toArray());
        $this->assertContains('Jumlah pembayaran minimal 0.01.', $validator->errors()->get('amount'));
    }

    public function test_special_characters_in_ref_no(): void
    {
        $data = $this->getValidData();
        $data['ref_no'] = 'REF-123_456#789@test.com';

        $request = $this->makeRequest($data);
        $validator = Validator::make($data, $this->getRulesWithoutDatabaseValidation(), $request->messages());

        $this->assertFalse($validator->fails());
    }
}
