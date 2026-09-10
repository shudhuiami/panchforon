<?php

namespace Database\Factories;

use App\Models\MealPlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MealPlan>
 */
class MealPlanFactory extends Factory
{
    protected $model = MealPlan::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => 'This week',
            'is_active' => true,
        ];
    }
}
