<?php

namespace App\Http\Resources;

use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin OrderItem
 */
class OrderItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'menuItemId' => $this->menu_item_id,
            'name' => $this->name,
            'variantLabel' => $this->variant_label,
            'unitPrice' => (float) $this->unit_price,
            'quantity' => $this->quantity,
            'notes' => $this->notes,
        ];
    }
}
