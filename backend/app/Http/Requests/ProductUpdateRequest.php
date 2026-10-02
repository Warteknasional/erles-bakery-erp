<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $productId = $this->route('product');
        if (is_object($productId)) {
            $productId = $productId->id;
        }

        return [
            'nama' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('products', 'nama')->ignore($productId)],
            'deskripsi' => ['nullable', 'string'],
            'harga' => ['sometimes', 'required', 'numeric', 'min:0'],
            'stok' => ['nullable', 'integer', 'min:0'],
            'kategori' => ['sometimes', 'required', 'string', 'max:100'],
            'gambar' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nama.required' => 'Nama produk wajib diisi.',
            'nama.unique' => 'Nama produk sudah digunakan.',
            'harga.required' => 'Harga produk wajib diisi.',
            'harga.numeric' => 'Harga produk harus berupa angka.',
            'harga.min' => 'Harga produk tidak boleh negatif.',
            'kategori.required' => 'Kategori produk wajib diisi.',
        ];
    }
}
