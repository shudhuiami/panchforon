<?php

namespace Database\Factories;

use App\Enums\CreatorApplicationStatus;
use App\Models\CreatorApplication;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CreatorApplication>
 */
class CreatorApplicationFactory extends Factory
{
    protected $model = CreatorApplication::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'status' => CreatorApplicationStatus::Pending,
            'pitch' => fake()->paragraph(),
            'youtube_channel_url' => fake()->optional()->url(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CreatorApplicationStatus::Pending,
            'reviewed_by' => null,
            'reviewed_at' => null,
            'review_note' => null,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CreatorApplicationStatus::Approved,
            'reviewed_at' => now(),
        ]);
    }

    public function declined(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CreatorApplicationStatus::Declined,
            'reviewed_at' => now(),
            'review_note' => fake()->sentence(),
        ]);
    }
}
