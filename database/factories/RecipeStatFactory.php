<?php

namespace Database\Factories;

use App\Models\Recipe;
use App\Models\RecipeStat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecipeStat>
 */
class RecipeStatFactory extends Factory
{
    protected $model = RecipeStat::class;

    public function definition(): array
    {
        return [
            'recipe_id' => Recipe::factory(),
            'ratings_count' => 0,
            'ratings_avg' => null,
            'bayesian_score' => 3.8,
            'updated_at' => now(),
        ];
    }
}
