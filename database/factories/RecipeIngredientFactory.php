<?php

namespace Database\Factories;

use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecipeIngredient>
 */
class RecipeIngredientFactory extends Factory
{
    protected $model = RecipeIngredient::class;

    public function definition(): array
    {
        return [
            'recipe_id' => Recipe::factory(),
            'ingredient_id' => Ingredient::factory(),
            'quantity' => fake()->randomFloat(2, 0.5, 500),
            'unit' => fake()->randomElement(['g', 'kg', 'ml', 'l', 'tbsp', 'tsp', 'piece', 'cup']),
            'raw_text' => fake()->sentence(3),
            'position' => 0,
        ];
    }
}
