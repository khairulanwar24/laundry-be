<?php

namespace App\Http\Requests\Api\V1\Outlet;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreOutletRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'logo_path' => ['nullable', 'url'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama outlet wajib diisi.',
            'name.string' => 'Nama outlet harus berupa teks.',
            'name.max' => 'Nama outlet maksimal 120 karakter.',
            'address.string' => 'Alamat harus berupa teks.',
            'address.max' => 'Alamat maksimal 255 karakter.',
            'phone.string' => 'Nomor telepon harus berupa teks.',
            'phone.max' => 'Nomor telepon maksimal 30 karakter.',
            'logo_path.url' => 'Logo harus berupa URL yang valid.',
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
