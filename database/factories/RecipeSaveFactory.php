<?php

namespace Database\Factories;

use App\Models\Recipe;
use App\Models\RecipeSave;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecipeSave>
 */
class RecipeSaveFactory extends Factory
{
    protected $model = RecipeSave::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'recipe_id' => Recipe::factory(),
        ];
    }
}
