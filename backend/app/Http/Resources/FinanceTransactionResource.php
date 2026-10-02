<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FinanceTransactionResource extends JsonResource
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
            'tipe' => $this->tipe,
            'nominal' => (float) $this->nominal,
            'kategori' => $this->kategori,
            'catatan' => $this->catatan,
            'tanggal' => $this->tanggal?->format('Y-m-d'),
            'user_id' => $this->user_id,
            'user' => new UserResource($this->whenLoaded('user')),
            'order_id' => $this->order_id,
            'order' => new OrderResource($this->whenLoaded('order')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
