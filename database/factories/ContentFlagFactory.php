<?php

namespace Database\Factories;

use App\Enums\FlagReason;
use App\Enums\FlagStatus;
use App\Models\ContentFlag;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContentFlag>
 */
class ContentFlagFactory extends Factory
{
    protected $model = ContentFlag::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'recipe_id' => Recipe::factory(),
            'user_id' => User::factory(),
            'reason' => fake()->randomElement(FlagReason::cases()),
            'note' => fake()->optional()->sentence(),
            'status' => FlagStatus::Open,
        ];
    }

    public function dismissed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => FlagStatus::Dismissed,
            'reviewed_at' => now(),
        ]);
    }

    public function actioned(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => FlagStatus::Actioned,
            'reviewed_at' => now(),
        ]);
    }
}
