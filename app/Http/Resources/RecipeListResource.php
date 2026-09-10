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
            'title' => $this->title,
            'slug' => $this->slug,
            'cuisine' => $this->cuisine,
            'category' => $this->category,
            'image_url' => $this->image_url,
            'servings' => $this->servings,
            'ratings_count' => $this->stat !== null ? (int) $this->stat->ratings_count : 0,
            'ratings_avg' => $this->stat !== null ? $this->stat->ratings_avg : null,
            'bayesian_score' => $this->stat !== null ? $this->stat->bayesian_score : null,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
