<?php

namespace Database\Factories;

use App\Enums\ModerationStatus;
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
            'moderation_status' => ModerationStatus::Approved,
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

    /**
     * A user submission that has not been reviewed yet.
     */
    public function awaitingModeration(): static
    {
        return $this->state(fn (array $attributes) => [
            'source' => RecipeSource::User,
            'moderation_status' => ModerationStatus::Pending,
        ]);
    }

    /**
     * A recipe an admin has pulled from public view.
     */
    public function unpublished(): static
    {
        return $this->state(fn (array $attributes) => [
            'moderation_status' => ModerationStatus::Unpublished,
        ]);
    }

    /**
     * A recipe imported from TheMealDB rather than submitted by a user.
     */
    public function fromApi(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => null,
            'source' => RecipeSource::Api,
            'moderation_status' => ModerationStatus::Approved,
            'external_id' => (string) fake()->unique()->numberBetween(50000, 99999),
        ]);
    }
}
