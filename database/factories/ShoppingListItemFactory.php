<?php

namespace Database\Factories;

use App\Models\Ingredient;
use App\Models\MealPlan;
use App\Models\ShoppingListItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShoppingListItem>
 */
class ShoppingListItemFactory extends Factory
{
    protected $model = ShoppingListItem::class;

    public function definition(): array
    {
        return [
            'meal_plan_id' => MealPlan::factory(),
            'ingredient_id' => Ingredient::factory(),
            'display_name' => fake()->word(),
            'quantity' => fake()->randomFloat(2, 1, 1000),
            'unit' => 'g',
            'is_unmerged' => false,
            'source_note' => null,
            'is_checked' => false,
        ];
    }
}
