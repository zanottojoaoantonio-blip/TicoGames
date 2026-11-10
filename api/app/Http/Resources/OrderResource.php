<?php

namespace App\Http\Resources;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'customer_number' => (int) ($this->customer_number ?? Order::where('user_id', $this->user_id)
                ->where('id', '<=', $this->id)->count()),
            'status' => $this->status,
            'user_id' => $this->user_id,
            'created_at' => $this->created_at?->toISOString(),
            'paid_at' => $this->paid_at?->toISOString(),
            'shipping_cost' => '0.00',
            'total' => $this->total,
            'shipping_recipient_name' => $this->shipping_recipient_name,
            'shipping_postal_code' => $this->shipping_postal_code,
            'shipping_street' => $this->shipping_street,
            'shipping_number' => $this->shipping_number,
            'shipping_complement' => $this->shipping_complement,
            'shipping_neighborhood' => $this->shipping_neighborhood,
            'shipping_city' => $this->shipping_city,
            'shipping_state' => $this->shipping_state,
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
