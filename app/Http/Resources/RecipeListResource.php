<?php

namespace App\Http\Resources;

use App\Models\Recipe;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Recipe
 */
class RecipeListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'source' => $this->source->value,
            'moderation_status' => $this->moderation_status->value,
            'title' => $this->title,
            'name_bn' => $this->name_bn,
            'slug' => $this->slug,
            'cuisine' => $this->cuisine,
            'category' => $this->category,
            'image_url' => $this->image_url,
            'servings' => $this->servings,
            'prep_minutes' => $this->prep_minutes,
            'cook_minutes' => $this->cook_minutes,
            'total_minutes' => $this->totalMinutes(),
            'spice_level' => $this->spice_level?->value,
            'ratings_count' => $this->stat !== null ? (int) $this->stat->ratings_count : 0,
            'ratings_avg' => $this->stat !== null ? $this->stat->ratings_avg : null,
            'bayesian_score' => $this->stat !== null ? $this->stat->bayesian_score : null,
            'created_at' => $this->created_at?->toISOString(),
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
