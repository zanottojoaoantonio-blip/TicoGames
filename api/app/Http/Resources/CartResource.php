<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    // Seleciona explicitamente a saída, sem expor senha ou dados de outra conta.
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'subtotal' => $this->subtotal,
            'shipping_cost' => $this->shipping_cost,
            'total' => $this->total,
            'warnings' => $this->warnings ?? [],
            'items' => CartItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
