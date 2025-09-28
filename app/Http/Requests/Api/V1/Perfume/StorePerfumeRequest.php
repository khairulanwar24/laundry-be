<?php

namespace App\Http\Requests\Api\V1\Perfume;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StorePerfumeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'note' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama parfum wajib diisi.',
            'name.string' => 'Nama parfum harus berupa teks.',
            'name.max' => 'Nama parfum maksimal 120 karakter.',
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
