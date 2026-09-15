<?php

namespace App\Http\Resources;

use App\Models\RecipeIngredient;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin RecipeIngredient
 */
class RecipeIngredientResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ingredient_id' => $this->ingredient_id,
            'quantity' => $this->quantity,
            'unit' => $this->unit,
            'is_optional' => (bool) $this->is_optional,
            'note' => $this->note,
            'raw_text' => $this->raw_text,
            'position' => $this->position,
            'ingredient' => new IngredientResource($this->whenLoaded('ingredient')),
        ];
    }
}
