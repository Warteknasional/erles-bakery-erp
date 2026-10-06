<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'kode_pesanan' => $this->order?->kode_pesanan,
            'user_id' => $this->user_id,
            'user' => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'role' => $this->user->role,
            ] : null,
            'nominal' => (float) $this->nominal,
            'metode' => $this->metode,
            'tipe' => $this->tipe,
            'tanggal' => $this->tanggal instanceof \DateTimeInterface
                ? $this->tanggal->format('Y-m-d')
                : (string) $this->tanggal,
            'catatan' => $this->catatan,
            'bukti_bayar' => $this->bukti_bayar,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
