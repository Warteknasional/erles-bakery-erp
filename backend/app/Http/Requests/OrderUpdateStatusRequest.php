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
            'status' => ['required', 'string', Rule::in([
                'pending',
                'confirmed',
                'processing',
                'ready',
                'completed',
                'cancelled',
                'diproses',
                'selesai',
                'dibatalkan',
            ])],
            'catatan' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Status pesanan wajib diisi.',
            'status.in' => 'Status pesanan tidak valid. Pilih dari: pending, confirmed, processing, ready, completed, cancelled.',
        ];
    }
}
