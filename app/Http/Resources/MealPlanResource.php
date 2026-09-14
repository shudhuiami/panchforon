<?php

namespace App\Http\Resources;

use App\Models\MealPlan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin MealPlan
 */
class MealPlanResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'is_active' => $this->is_active,
            'starts_on' => $this->startDate()->toDateString(),
            'ends_on' => $this->endDate()->toDateString(),
            'day_count' => $this->dayCount(),
            'items' => MealPlanItemResource::collection($this->whenLoaded('items')),
            'items_count' => (int) ($this->items_count ?? $this->items()->count()),
            'shopping_items_count' => (int) ($this->shopping_list_items_count ?? $this->shoppingListItems()->count()),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
