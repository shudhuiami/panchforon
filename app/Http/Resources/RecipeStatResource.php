<?php

namespace App\Http\Resources;

use App\Models\RecipeStat;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin RecipeStat
 */
class RecipeStatResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'ratings_count' => $this->ratings_count,
            'ratings_avg' => $this->ratings_avg,
            'bayesian_score' => $this->bayesian_score,
            'updated_at' => $this->updated_at !== null ? (string) $this->updated_at : null,
        ];
    }
}
