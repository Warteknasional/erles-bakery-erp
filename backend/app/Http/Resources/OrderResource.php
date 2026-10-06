<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
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
            'customer_id' => $this->customer_id,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'kode_pesanan' => $this->kode_pesanan,
            'customer_name' => $this->customer_name,
            'customer_phone' => $this->customer_phone,
            'alamat' => $this->alamat,
            'catatan' => $this->catatan,
            'tanggal_ambil' => $this->tanggal_ambil?->format('Y-m-d'),
            'total_price' => (float) $this->total_price,
            'status' => $this->status,
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'items_count' => $this->whenCounted('items'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
