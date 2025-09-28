<?php

namespace App\Http\Requests\Api\V1\ServiceVariant;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreServiceVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'service_id' => ['required', 'exists:services,id'],
            'name' => ['required', 'string', 'max:120'],
            'unit' => ['required', 'in:kg,pcs,meter'],
            'price_per_unit' => ['required', 'numeric', 'min:0'],
            'tat_duration_hours' => ['required', 'integer', 'min:1'],
            'image_path' => ['nullable', 'url'],
            'note' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'service_id.required' => 'ID layanan wajib diisi.',
            'service_id.exists' => 'Layanan tidak ditemukan.',
            'name.required' => 'Nama varian layanan wajib diisi.',
            'name.string' => 'Nama varian layanan harus berupa teks.',
            'name.max' => 'Nama varian layanan maksimal 120 karakter.',
            'unit.required' => 'Satuan wajib diisi.',
            'unit.in' => 'Satuan harus kg, pcs, atau meter.',
            'price_per_unit.required' => 'Harga per satuan wajib diisi.',
            'price_per_unit.numeric' => 'Harga per satuan harus berupa angka.',
            'price_per_unit.min' => 'Harga per satuan minimal 0.',
            'tat_duration_hours.required' => 'Durasi TAT wajib diisi.',
            'tat_duration_hours.integer' => 'Durasi TAT harus berupa angka.',
            'tat_duration_hours.min' => 'Durasi TAT minimal 1 jam.',
            'image_path.url' => 'Path gambar harus berupa URL yang valid.',
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
