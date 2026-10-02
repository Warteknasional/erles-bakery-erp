<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OrderStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:20'],
            'alamat' => ['nullable', 'string'],
            'catatan' => ['nullable', 'string'],
            'tanggal_ambil' => ['nullable', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_name.required' => 'Nama pelanggan wajib diisi.',
            'customer_phone.required' => 'Nomor WhatsApp / telepon pelanggan wajib diisi.',
            'items.required' => 'Pesanan minimal harus memiliki 1 item produk.',
            'items.min' => 'Pesanan minimal harus memiliki 1 item produk.',
            'items.*.product_id.required' => 'Produk wajib dipilih.',
            'items.*.product_id.exists' => 'Produk yang dipilih tidak valid atau tidak ditemukan.',
            'items.*.qty.required' => 'Jumlah (qty) produk wajib diisi.',
            'items.*.qty.min' => 'Jumlah (qty) produk minimal 1.',
        ];
    }
}
