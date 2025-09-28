<?php

namespace App\Http\Requests\Api\V1\Order;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.service_variant_id' => ['required', 'integer', 'exists:service_variants,id'],
            'items.*.qty' => ['required', 'numeric', 'min:0.01'],
            'items.*.note' => ['nullable', 'string', 'max:1000'],
            'perfume_id' => ['nullable', 'integer', 'exists:perfumes,id'],
            'discount_id' => ['nullable', 'integer', 'exists:discounts,id'],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required' => 'ID pelanggan wajib diisi.',
            'customer_id.integer' => 'ID pelanggan harus berupa angka.',
            'customer_id.exists' => 'Pelanggan tidak ditemukan.',
            'items.required' => 'Item pesanan wajib diisi.',
            'items.array' => 'Item pesanan harus berupa array.',
            'items.min' => 'Minimal harus ada 1 item pesanan.',
            'items.*.service_variant_id.required' => 'ID varian layanan wajib diisi.',
            'items.*.service_variant_id.integer' => 'ID varian layanan harus berupa angka.',
            'items.*.service_variant_id.exists' => 'Varian layanan tidak ditemukan.',
            'items.*.qty.required' => 'Jumlah item wajib diisi.',
            'items.*.qty.numeric' => 'Jumlah item harus berupa angka.',
            'items.*.qty.min' => 'Jumlah item minimal 0.01.',
            'items.*.note.string' => 'Catatan item harus berupa teks.',
            'items.*.note.max' => 'Catatan item maksimal 1000 karakter.',
            'perfume_id.integer' => 'ID parfum harus berupa angka.',
            'perfume_id.exists' => 'Parfum tidak ditemukan.',
            'discount_id.integer' => 'ID diskon harus berupa angka.',
            'discount_id.exists' => 'Diskon tidak ditemukan.',
            'note.string' => 'Catatan harus berupa teks.',
            'note.max' => 'Catatan maksimal 2000 karakter.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'data' => null,
                'errors' => $validator->errors(),
                'meta' => null,
            ], 422)
        );
    }
}
