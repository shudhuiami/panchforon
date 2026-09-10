<?php

namespace Database\Factories;

use App\Enums\RecipeSource;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Recipe>
 */
class RecipeFactory extends Factory
{
    protected $model = Recipe::class;

    public function definition(): array
    {
        $title = fake()->sentence(3);

        return [
            'user_id' => User::factory(),
            'source' => RecipeSource::User,
            'external_id' => null,
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(100, 99999),
            'cuisine' => fake()->randomElement(['Bangladeshi', 'Indian', 'Italian', 'Mexican', 'Thai']),
            'category' => fake()->randomElement(['Curry', 'Rice', 'Vegetarian', 'Chicken', 'Seafood']),
            'instructions' => fake()->paragraphs(3, true),
            'image_url' => fake()->imageUrl(),
            'servings' => 4,
            'source_url' => null,
        ];
    }
}
