<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FinanceTransactionStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipe' => ['required', 'string', Rule::in(['pemasukan', 'pengeluaran'])],
            'nominal' => ['required', 'numeric', 'min:0.01'],
            'kategori' => ['required', 'string', 'max:100'],
            'catatan' => ['nullable', 'string'],
            'tanggal' => ['required', 'date'],
            'order_id' => ['nullable', 'integer', 'exists:orders,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'tipe.required' => 'Tipe transaksi wajib diisi.',
            'tipe.in' => 'Tipe transaksi harus berupa pemasukan atau pengeluaran.',
            'nominal.required' => 'Nominal transaksi wajib diisi.',
            'nominal.numeric' => 'Nominal transaksi harus berupa angka.',
            'nominal.min' => 'Nominal transaksi minimal Rp 0.01.',
            'kategori.required' => 'Kategori transaksi wajib diisi.',
            'tanggal.required' => 'Tanggal transaksi wajib diisi.',
            'tanggal.date' => 'Format tanggal transaksi tidak valid.',
            'order_id.exists' => 'Pesanan yang ditautkan tidak ditemukan.',
        ];
    }
}
