<?php

namespace Database\Factories;

use App\Models\Ingredient;
use App\Models\IngredientAlias;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IngredientAlias>
 */
class IngredientAliasFactory extends Factory
{
    protected $model = IngredientAlias::class;

    public function definition(): array
    {
        return [
            'ingredient_id' => Ingredient::factory(),
            'alias' => fake()->unique()->word(),
        ];
    }
}
