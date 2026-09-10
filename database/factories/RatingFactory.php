<?php

namespace Database\Factories;

use App\Models\Rating;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Rating>
 */
class RatingFactory extends Factory
{
    protected $model = Rating::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'recipe_id' => Recipe::factory(),
            'stars' => fake()->numberBetween(1, 5),
            'review' => fake()->optional()->sentence(),
        ];
    }
}
