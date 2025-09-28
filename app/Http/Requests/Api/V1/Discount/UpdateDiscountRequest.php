<?php

namespace App\Http\Requests\Api\V1\Discount;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateDiscountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'type' => ['sometimes', 'required', 'in:nominal,percent'],
            'value' => ['sometimes', 'required', 'numeric', 'min:0'],
            'note' => ['sometimes', 'nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama diskon wajib diisi.',
            'name.string' => 'Nama diskon harus berupa teks.',
            'name.max' => 'Nama diskon maksimal 120 karakter.',
            'type.required' => 'Tipe diskon wajib diisi.',
            'type.in' => 'Tipe diskon harus nominal atau percent.',
            'value.required' => 'Nilai diskon wajib diisi.',
            'value.numeric' => 'Nilai diskon harus berupa angka.',
            'value.min' => 'Nilai diskon minimal 0.',
            'note.string' => 'Catatan harus berupa teks.',
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
