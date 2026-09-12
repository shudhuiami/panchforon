<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A cook as everyone else sees them. Deliberately narrow: no email, no
 * account flags, nothing an admin screen would show.
 *
 * @mixin User
 */
class CookResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'recipes_count' => $this->whenCounted('recipes'),
            'ratings_count' => $this->whenCounted('ratings'),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
