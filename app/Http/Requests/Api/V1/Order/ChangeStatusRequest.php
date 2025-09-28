<?php

namespace App\Http\Requests\Api\V1\Order;

use App\Models\Order;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ChangeStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'to' => [
                'required',
                'string',
                'in:'.implode(',', [
                    Order::STATUS_ANTRIAN,
                    Order::STATUS_PROSES,
                    Order::STATUS_SIAP_DIAMBIL,
                    Order::STATUS_SELESAI,
                    Order::STATUS_BATAL,
                ]),
            ],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'to.required' => 'Status tujuan wajib diisi.',
            'to.string' => 'Status tujuan harus berupa teks.',
            'to.in' => 'Status tujuan tidak valid. Pilihan yang tersedia: '.implode(', ', [
                Order::STATUS_ANTRIAN,
                Order::STATUS_PROSES,
                Order::STATUS_SIAP_DIAMBIL,
                Order::STATUS_SELESAI,
                Order::STATUS_BATAL,
            ]),
            'notes.string' => 'Catatan harus berupa teks.',
            'notes.max' => 'Catatan maksimal 1000 karakter.',
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
