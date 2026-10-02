<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FinanceTransactionUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipe' => ['sometimes', 'required', 'string', Rule::in(['pemasukan', 'pengeluaran'])],
            'nominal' => ['sometimes', 'required', 'numeric', 'min:0.01'],
            'kategori' => ['sometimes', 'required', 'string', 'max:100'],
            'catatan' => ['nullable', 'string'],
            'tanggal' => ['sometimes', 'required', 'date'],
            'order_id' => ['nullable', 'integer', 'exists:orders,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'tipe.in' => 'Tipe transaksi harus berupa pemasukan atau pengeluaran.',
            'nominal.numeric' => 'Nominal transaksi harus berupa angka.',
            'nominal.min' => 'Nominal transaksi minimal Rp 0.01.',
            'tanggal.date' => 'Format tanggal transaksi tidak valid.',
            'order_id.exists' => 'Pesanan yang ditautkan tidak ditemukan.',
        ];
    }
}
