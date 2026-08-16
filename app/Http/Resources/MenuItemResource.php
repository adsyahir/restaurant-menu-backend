<?php

namespace App\Http\Resources;

use App\Models\MenuItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin MenuItem
 */
class MenuItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'categoryId' => $this->category_id,
            'name' => $this->name,
            'description' => $this->description,
            'basePrice' => (float) $this->base_price,
            'isAvailable' => $this->is_available,
            'dietaryTags' => $this->dietary_tags ?? [],
            'variants' => $this->variants ?? [],
            'addOns' => $this->add_ons ?? [],
            'imageUrl' => $this->image_url,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }
}
