<?php

namespace App\Http\Resources;

use App\Models\MealPlan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A plan as the history list shows it: the shape of the week without the
 * dishes themselves.
 *
 * @mixin MealPlan
 */
class MealPlanSummaryResource extends JsonResource
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
            'items_count' => (int) ($this->items_count ?? $this->items()->count()),
            'servings_total' => (int) ($this->items_sum_servings ?? 0),
            'cuisines' => $this->cuisineNames(),
            'shopping_items_count' => (int) ($this->shopping_list_items_count ?? $this->shoppingListItems()->count()),
            'shopping_checked_count' => (int) ($this->shopping_checked_count ?? 0),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
