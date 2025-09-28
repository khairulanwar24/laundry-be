<?php

namespace App\Http\Requests\Api\V1\PaymentMethod;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StorePaymentMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category' => ['required', 'in:cash,transfer,e_wallet'],
            'name' => ['required', 'string', 'max:120'],
            'owner_name' => ['nullable', 'string', 'max:120'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:30'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'category.required' => 'Kategori wajib diisi.',
            'category.in' => 'Kategori harus cash, transfer, atau e_wallet.',
            'name.required' => 'Nama metode pembayaran wajib diisi.',
            'name.string' => 'Nama metode pembayaran harus berupa teks.',
            'name.max' => 'Nama metode pembayaran maksimal 120 karakter.',
            'owner_name.string' => 'Nama pemilik harus berupa teks.',
            'owner_name.max' => 'Nama pemilik maksimal 120 karakter.',
            'tags.array' => 'Tags harus berupa array.',
            'tags.*.string' => 'Setiap tag harus berupa teks.',
            'tags.*.max' => 'Setiap tag maksimal 30 karakter.',
            'is_active.boolean' => 'Status aktif harus berupa boolean.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        $response = response()->json([
            'success' => false,
            'message' => 'Validasi gagal',
            'data' => null,
            'errors' => $validator->errors(),
            'meta' => null,
        ], 422);

        throw new HttpResponseException($response);
    }
}
