<?php

namespace App\Http\Resources;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Slim, customer-facing view of an order for the public tracking page.
 * Deliberately omits staff-only fields (internal notes, payment method,
 * created-by, ids) — only what a customer needs to follow their order.
 *
 * @mixin Order
 */
class PublicOrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'orderNumber' => $this->order_number,
            'status' => $this->status,
            'tableLabel' => $this->whenLoaded('table', fn () => $this->table?->label),
            'placedAt' => $this->placed_at,
            'total' => (float) $this->total,
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'name' => $item->name,
                'variantLabel' => $item->variant_label,
                'quantity' => $item->quantity,
            ])),
        ];
    }
}
