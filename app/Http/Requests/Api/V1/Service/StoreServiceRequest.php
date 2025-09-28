<?php

namespace App\Http\Requests\Api\V1\Service;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'priority_score' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'process_steps_json' => ['sometimes', 'array'],
            'process_steps_json.*' => ['string', 'in:cuci,kering,setrika'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama layanan wajib diisi.',
            'name.string' => 'Nama layanan harus berupa teks.',
            'name.max' => 'Nama layanan maksimal 120 karakter.',
            'priority_score.integer' => 'Skor prioritas harus berupa angka.',
            'priority_score.min' => 'Skor prioritas minimal 0.',
            'priority_score.max' => 'Skor prioritas maksimal 100.',
            'process_steps_json.array' => 'Langkah proses harus berupa array.',
            'process_steps_json.*.string' => 'Setiap langkah proses harus berupa teks.',
            'process_steps_json.*.in' => 'Langkah proses harus cuci, kering, atau setrika.',
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
