<?php

namespace App\Http\Resources;

use App\Models\Recipe;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Recipe
 */
class RecipeDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /**
         * The detail route has no auth middleware, so the default guard never
         * sees a bearer token; ask the sanctum guard explicitly.
         */
        $user = $request->user('sanctum');

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'author' => new UserResource($this->whenLoaded('user')),
            'source' => $this->source->value,
            'moderation_status' => $this->moderation_status->value,
            'external_id' => $this->external_id,
            'title' => $this->title,
            'name_bn' => $this->name_bn,
            'slug' => $this->slug,
            'cuisine' => $this->cuisine,
            'category' => $this->category,
            'instructions' => $this->instructions,
            'image_url' => $this->image_url,
            'servings' => $this->servings,
            'prep_minutes' => $this->prep_minutes,
            'cook_minutes' => $this->cook_minutes,
            'total_minutes' => $this->totalMinutes(),
            'spice_level' => $this->spice_level?->value,
            'source_url' => $this->source_url,
            'ingredients' => RecipeIngredientResource::collection($this->whenLoaded('ingredients')),
            'stat' => new RecipeStatResource($this->whenLoaded('stat')),
            'ratings' => RatingResource::collection($this->whenLoaded('ratings')),
            'user_rating' => $user !== null && $this->relationLoaded('ratings')
                ? $this->ratings->firstWhere('user_id', $user->id)?->stars
                : null,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    /**
     * How long the dish takes end to end. A recipe that records only one of the
     * two times still reports that one; null means nothing is known at all,
     * which a card can show as "—" rather than a misleading zero.
     */
    private function totalMinutes(): ?int
    {
        if ($this->prep_minutes === null && $this->cook_minutes === null) {
            return null;
        }

        return (int) $this->prep_minutes + (int) $this->cook_minutes;
    }
}
