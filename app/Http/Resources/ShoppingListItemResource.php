<?php

namespace App\Http\Resources;

use App\Models\ShoppingListItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ShoppingListItem
 */
class ShoppingListItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'meal_plan_id' => $this->meal_plan_id,
            'ingredient_id' => $this->ingredient_id,
            'display_name' => $this->display_name,
            'quantity' => $this->quantity,
            'unit' => $this->unit,
            'is_unmerged' => $this->is_unmerged,
            'source_note' => $this->source_note,
            'is_checked' => $this->is_checked,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
