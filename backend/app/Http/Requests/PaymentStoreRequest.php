<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $hasRouteOrder = (bool) $this->route('order');

        return [
            'order_id' => [
                $hasRouteOrder ? 'nullable' : 'required',
                'integer',
                Rule::exists('orders', 'id'),
            ],
            'nominal' => ['required', 'numeric', 'min:1'],
            'metode' => [
                'required',
                'string',
                Rule::in(['cash', 'tunai', 'transfer', 'bank_transfer', 'qris', 'debit', 'kartu_kredit']),
            ],
            'tipe' => [
                'required',
                'string',
                Rule::in(['dp', 'lunas', 'cicilan', 'pelunasan']),
            ],
            'tanggal' => ['nullable', 'date'],
            'catatan' => ['nullable', 'string', 'max:500'],
            'bukti_bayar' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'order_id.required' => 'ID pesanan wajib dipilih.',
            'order_id.exists' => 'Pesanan tidak ditemukan.',
            'nominal.required' => 'Nominal pembayaran wajib diisi.',
            'nominal.numeric' => 'Nominal pembayaran harus berupa angka.',
            'nominal.min' => 'Nominal pembayaran minimal Rp 1.',
            'metode.required' => 'Metode pembayaran wajib diisi.',
            'metode.in' => 'Metode pembayaran harus salah satu dari: cash, tunai, transfer, bank_transfer, qris, debit, kartu_kredit.',
            'tipe.required' => 'Tipe pembayaran wajib diisi.',
            'tipe.in' => 'Tipe pembayaran harus salah satu dari: dp, lunas, cicilan, pelunasan.',
            'tanggal.date' => 'Format tanggal pembayaran tidak valid.',
        ];
    }
}
