<?php

namespace Database\Factories;

use App\Enums\UnitDimension;
use App\Models\Ingredient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ingredient>
 */
class IngredientFactory extends Factory
{
    protected $model = Ingredient::class;

    public function definition(): array
    {
        return [
            'canonical_name' => fake()->unique()->word(),
            'default_dimension' => fake()->randomElement([
                UnitDimension::Mass,
                UnitDimension::Volume,
                UnitDimension::Count,
                UnitDimension::None,
            ]),
        ];
    }
}
