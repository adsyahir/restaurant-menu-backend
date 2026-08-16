<?php

namespace App\Http\Resources;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Order
 */
class OrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'orderNumber' => $this->order_number,
            'trackToken' => $this->public_token,
            'tableId' => $this->table_id,
            'tableLabel' => $this->whenLoaded('table', fn () => $this->table?->label),
            'createdByName' => $this->whenLoaded('creator', fn () => $this->creator?->name),
            'status' => $this->status,
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'subtotal' => (float) $this->subtotal,
            'total' => (float) $this->total,
            'notes' => $this->notes,
            'paymentMethod' => $this->payment_method,
            'placedAt' => $this->placed_at,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }
}
