<?php

namespace App\Http\Resources;

use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Workspace
 */
class WorkspaceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'slug' => $this->slug,
            'emoji' => $this->emoji,
            'cuisine' => $this->cuisine,
            'address' => $this->address,
            'state' => $this->state,
            'city' => $this->city,
            'postcode' => $this->postcode,
            'countryCode' => $this->country_code,
            'currency' => $this->currency,
            'timezone' => $this->timezone,
            'plan' => $this->plan,
            'subscriptionStatus' => $this->subscription_status,
            'renewsOn' => $this->renews_on?->toDateString(),
            'ownerId' => $this->owner_id,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }
}
