<?php

namespace Database\Factories;

use App\Enums\MealSlot;
use App\Models\MealPlan;
use App\Models\MealPlanItem;
use App\Models\Recipe;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MealPlanItem>
 */
class MealPlanItemFactory extends Factory
{
    protected $model = MealPlanItem::class;

    public function definition(): array
    {
        return [
            'meal_plan_id' => MealPlan::factory(),
            'recipe_id' => Recipe::factory(),
            'servings' => 4,
            'planned_for' => CarbonImmutable::today(),
            'meal_slot' => MealSlot::Dinner,
        ];
    }

    /**
     * A dish the cook has picked but not given a day to yet.
     */
    public function unscheduled(): self
    {
        return $this->state(['planned_for' => null]);
    }

    public function on(CarbonImmutable $day, MealSlot $slot = MealSlot::Dinner): self
    {
        return $this->state([
            'planned_for' => $day,
            'meal_slot' => $slot,
        ]);
    }
}
