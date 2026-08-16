<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A workspace member (user) with their per-workspace role, taken from the
 * `workspace_user` pivot.
 *
 * @mixin User
 */
class StaffResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->whenPivotLoaded('workspace_user', fn () => $this->pivot->role),
            'isActive' => $this->whenPivotLoaded('workspace_user', fn () => (bool) $this->pivot->is_active),
            'createdAt' => $this->whenPivotLoaded('workspace_user', fn () => $this->pivot->created_at),
        ];
    }
}
