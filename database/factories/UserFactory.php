<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the user may reach the Filament admin panel.
     *
     * Both columns are set while both exist: is_admin is still what the
     * application reads, and role is what it will read, so a factory that set
     * only one of them would build a user the backfill would never produce.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_admin' => true,
            'role' => UserRole::Admin,
        ]);
    }

    /**
     * Indicate that the user publishes without review and reaches the studio.
     */
    public function creator(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Creator,
        ]);
    }

    /**
     * Indicate that the user is suspended and therefore locked out.
     */
    public function suspended(?string $reason = null): static
    {
        return $this->state(fn (array $attributes) => [
            'suspended_at' => now(),
            'suspension_reason' => $reason ?? 'Repeated policy violations',
        ]);
    }
}
