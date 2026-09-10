<?php

namespace App\Http\Resources;

use App\Models\MealPlanItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin MealPlanItem
 */
class MealPlanItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'meal_plan_id' => $this->meal_plan_id,
            'recipe_id' => $this->recipe_id,
            'servings' => $this->servings,
            'recipe' => new RecipeListResource($this->whenLoaded('recipe')),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
