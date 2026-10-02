<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrderUpdateStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(['pending', 'diproses', 'selesai', 'dibatalkan'])],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Status pesanan wajib diisi.',
            'status.in' => 'Status pesanan harus salah satu dari: pending, diproses, selesai, dibatalkan.',
        ];
    }
}
