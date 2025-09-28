<?php

namespace Tests\Unit;

use App\Http\Requests\Api\V1\Order\StoreOrderRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Tests\TestCase;

class StoreOrderRequestTest extends TestCase
{
    protected function getValidData(): array
    {
        return [
            'customer_id' => 1,
            'items' => [
                [
                    'service_variant_id' => 1,
                    'qty' => 2.5,
                    'note' => 'Test note for item 1',
                ],
                [
                    'service_variant_id' => 2,
                    'qty' => 1,
                    'note' => null,
                ],
            ],
            'perfume_id' => 1,
            'discount_id' => 1,
            'note' => 'Test order note',
        ];
    }

    protected function makeRequest(array $data = []): StoreOrderRequest
    {
        $request = new StoreOrderRequest;
        $request->replace($data);

        return $request;
    }

    protected function getRulesWithoutDatabaseValidation(): array
    {
        return [
            'customer_id' => ['required', 'integer'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.service_variant_id' => ['required', 'integer'],
            'items.*.qty' => ['required', 'numeric', 'min:0.01'],
            'items.*.note' => ['nullable', 'string', 'max:1000'],
            'perfume_id' => ['nullable', 'integer'],
            'discount_id' => ['nullable', 'integer'],
            'note' => ['nullable', 'string', 'max:2000'],
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

    public function test_customer_id_is_required(): void
    {
        $data = $this->getValidData();
        unset($data['customer_id']);

        $request = $this->makeRequest($data);
        $validator = Validator::make($data, $this->getRulesWithoutDatabaseValidation(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('customer_id', $validator->errors()->toArray());
        $this->assertContains('ID pelanggan wajib diisi.', $validator->errors()->get('customer_id'));
    }

    public function test_customer_id_must_be_integer(): void
    {
        $data = $this->getValidData();
        $data['customer_id'] = 'not-a-number';

        $request = $this->makeRequest($data);
        $validator = Validator::make($data, $this->getRulesWithoutDatabaseValidation(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('customer_id', $validator->errors()->toArray());
        $this->assertContains('ID pelanggan harus berupa angka.', $validator->errors()->get('customer_id'));
    }

    public function test_items_are_required(): void
    {
        $data = $this->getValidData();
        unset($data['items']);

        $request = $this->makeRequest($data);
        $validator = Validator::make($data, $this->getRulesWithoutDatabaseValidation(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('items', $validator->errors()->toArray());
        $this->assertContains('Item pesanan wajib diisi.', $validator->errors()->get('items'));
    }

    public function test_items_must_be_array(): void
    {
        $data = $this->getValidData();
        $data['items'] = 'not-an-array';

        $request = $this->makeRequest($data);
        $validator = Validator::make($data, $this->getRulesWithoutDatabaseValidation(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('items', $validator->errors()->toArray());
        $this->assertContains('Item pesanan harus berupa array.', $validator->errors()->get('items'));
    }

    public function test_items_minimum_one_required(): void
    {
        $data = $this->getValidData();
        $data['items'] = [];

        $request = $this->makeRequest($data);
        $validator = Validator::make($data, $this->getRulesWithoutDatabaseValidation(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('items', $validator->errors()->toArray());

        // Since empty array triggers 'required' rule first, check for that message
        $actualErrors = $validator->errors()->get('items');
        $this->assertContains('Item pesanan wajib diisi.', $actualErrors);
    }

    public function test_item_service_variant_id_is_required(): void
    {
        $data = $this->getValidData();
        unset($data['items'][0]['service_variant_id']);

        $request = $this->makeRequest($data);
        $validator = Validator::make($data, $this->getRulesWithoutDatabaseValidation(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('items.0.service_variant_id', $validator->errors()->toArray());
        $this->assertContains('ID varian layanan wajib diisi.', $validator->errors()->get('items.0.service_variant_id'));
    }

    public function test_item_service_variant_id_must_be_integer(): void
    {
        $data = $this->getValidData();
        $data['items'][0]['service_variant_id'] = 'not-a-number';

        $request = $this->makeRequest($data);
        $validator = Validator::make($data, $this->getRulesWithoutDatabaseValidation(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('items.0.service_variant_id', $validator->errors()->toArray());
        $this->assertContains('ID varian layanan harus berupa angka.', $validator->errors()->get('items.0.service_variant_id'));
    }

    public function test_item_qty_is_required(): void
    {
        $data = $this->getValidData();
        unset($data['items'][0]['qty']);

        $request = $this->makeRequest($data);
        $validator = Validator::make($data, $this->getRulesWithoutDatabaseValidation(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('items.0.qty', $validator->errors()->toArray());
        $this->assertContains('Jumlah item wajib diisi.', $validator->errors()->get('items.0.qty'));
    }

    public function test_item_qty_must_be_numeric(): void
    {
        $data = $this->getValidData();
        $data['items'][0]['qty'] = 'not-a-number';

        $request = $this->makeRequest($data);
        $validator = Validator::make($data, $this->getRulesWithoutDatabaseValidation(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('items.0.qty', $validator->errors()->toArray());
        $this->assertContains('Jumlah item harus berupa angka.', $validator->errors()->get('items.0.qty'));
    }

    public function test_item_qty_minimum_value(): void
    {
        $data = $this->getValidData();
        $data['items'][0]['qty'] = 0;

        $request = $this->makeRequest($data);
        $validator = Validator::make($data, $this->getRulesWithoutDatabaseValidation(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('items.0.qty', $validator->errors()->toArray());
        $this->assertContains('Jumlah item minimal 0.01.', $validator->errors()->get('items.0.qty'));
    }

    public function test_item_note_is_optional(): void
    {
        $data = $this->getValidData();
        unset($data['items'][0]['note']);

        $request = $this->makeRequest($data);
        $validator = Validator::make($data, $this->getRulesWithoutDatabaseValidation(), $request->messages());

        $this->assertFalse($validator->fails());
    }

    public function test_item_note_must_be_string(): void
    {
        $data = $this->getValidData();
        $data['items'][0]['note'] = 123;

        $request = $this->makeRequest($data);
        $validator = Validator::make($data, $this->getRulesWithoutDatabaseValidation(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('items.0.note', $validator->errors()->toArray());
        $this->assertContains('Catatan item harus berupa teks.', $validator->errors()->get('items.0.note'));
    }

    public function test_item_note_maximum_length(): void
    {
        $data = $this->getValidData();
        $data['items'][0]['note'] = str_repeat('a', 1001);

        $request = $this->makeRequest($data);
        $validator = Validator::make($data, $this->getRulesWithoutDatabaseValidation(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('items.0.note', $validator->errors()->toArray());
        $this->assertContains('Catatan item maksimal 1000 karakter.', $validator->errors()->get('items.0.note'));
    }

    public function test_perfume_id_is_optional(): void
    {
        $data = $this->getValidData();
        unset($data['perfume_id']);

        $request = $this->makeRequest($data);
        $validator = Validator::make($data, $this->getRulesWithoutDatabaseValidation(), $request->messages());

        $this->assertFalse($validator->fails());
    }

    public function test_perfume_id_must_be_integer(): void
    {
        $data = $this->getValidData();
        $data['perfume_id'] = 'not-a-number';

        $request = $this->makeRequest($data);
        $validator = Validator::make($data, $this->getRulesWithoutDatabaseValidation(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('perfume_id', $validator->errors()->toArray());
        $this->assertContains('ID parfum harus berupa angka.', $validator->errors()->get('perfume_id'));
    }

    public function test_discount_id_is_optional(): void
    {
        $data = $this->getValidData();
        unset($data['discount_id']);

        $request = $this->makeRequest($data);
        $validator = Validator::make($data, $this->getRulesWithoutDatabaseValidation(), $request->messages());

        $this->assertFalse($validator->fails());
    }

    public function test_discount_id_must_be_integer(): void
    {
        $data = $this->getValidData();
        $data['discount_id'] = 'not-a-number';

        $request = $this->makeRequest($data);
        $validator = Validator::make($data, $this->getRulesWithoutDatabaseValidation(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('discount_id', $validator->errors()->toArray());
        $this->assertContains('ID diskon harus berupa angka.', $validator->errors()->get('discount_id'));
    }

    public function test_note_is_optional(): void
    {
        $data = $this->getValidData();
        unset($data['note']);

        $request = $this->makeRequest($data);
        $validator = Validator::make($data, $this->getRulesWithoutDatabaseValidation(), $request->messages());

        $this->assertFalse($validator->fails());
    }

    public function test_note_must_be_string(): void
    {
        $data = $this->getValidData();
        $data['note'] = 123;

        $request = $this->makeRequest($data);
        $validator = Validator::make($data, $this->getRulesWithoutDatabaseValidation(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('note', $validator->errors()->toArray());
        $this->assertContains('Catatan harus berupa teks.', $validator->errors()->get('note'));
    }

    public function test_note_maximum_length(): void
    {
        $data = $this->getValidData();
        $data['note'] = str_repeat('a', 2001);

        $request = $this->makeRequest($data);
        $validator = Validator::make($data, $this->getRulesWithoutDatabaseValidation(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('note', $validator->errors()->toArray());
        $this->assertContains('Catatan maksimal 2000 karakter.', $validator->errors()->get('note'));
    }

    public function test_failed_validation_throws_http_response_exception(): void
    {
        $data = ['customer_id' => null]; // Invalid data
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
        $data = ['customer_id' => null]; // Invalid data
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

    public function test_multiple_items_validation(): void
    {
        $data = $this->getValidData();
        $data['items'][] = [
            'service_variant_id' => 3,
            'qty' => 0.5,
            'note' => 'Third item note',
        ];

        $request = $this->makeRequest($data);
        $validator = Validator::make($data, $this->getRulesWithoutDatabaseValidation(), $request->messages());

        $this->assertFalse($validator->fails());
    }

    public function test_decimal_qty_values(): void
    {
        $data = $this->getValidData();
        $data['items'][0]['qty'] = 1.5;
        $data['items'][1]['qty'] = 0.25;

        $request = $this->makeRequest($data);
        $validator = Validator::make($data, $this->getRulesWithoutDatabaseValidation(), $request->messages());

        $this->assertFalse($validator->fails());
    }
}
