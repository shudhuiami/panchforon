<?php

namespace Database\Factories;

use App\Models\MealPlan;
use App\Models\MealPlanItem;
use App\Models\Recipe;
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
        ];
    }
}
