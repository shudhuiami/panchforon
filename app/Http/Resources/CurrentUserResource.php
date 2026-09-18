<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The signed-in user's own account. Carries fields that must never appear on
 * a public payload; see UserResource for what other people get to see.
 *
 * is_admin is derived from the role rather than read from the column it used
 * to mirror, so the field the SPA already reads keeps working once that column
 * goes.
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
            'is_admin' => $this->isAdmin(),
            'role' => $this->role->value,
            'email_verified_at' => $this->email_verified_at?->toISOString(),
            'recipes_count' => $this->whenCounted('recipes'),
            'ratings_count' => $this->whenCounted('ratings'),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
