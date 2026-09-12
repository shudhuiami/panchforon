<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The signed-in user's own account. Carries fields that must never appear on
 * a public payload; see UserResource for what other people get to see.
 *
 * @mixin User
 */
class CurrentUserResource extends JsonResource
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
            'is_admin' => (bool) $this->is_admin,
            'email_verified_at' => $this->email_verified_at?->toISOString(),
            'recipes_count' => $this->whenCounted('recipes'),
            'ratings_count' => $this->whenCounted('ratings'),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
