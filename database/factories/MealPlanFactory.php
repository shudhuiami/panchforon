<?php

namespace Database\Factories;

use App\Models\MealPlan;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MealPlan>
 */
class MealPlanFactory extends Factory
{
    protected $model = MealPlan::class;

    public function definition(): array
    {
        $startsOn = CarbonImmutable::today();

        return [
            'user_id' => User::factory(),
            'name' => 'This week',
            'starts_on' => $startsOn,
            'ends_on' => $startsOn->addDays(MealPlan::DEFAULT_DAYS - 1),
            'is_active' => true,
        ];
    }

    /**
     * A plan that has already been cooked through: still readable, no longer
     * the one being edited.
     */
    public function past(int $weeksAgo = 1): self
    {
        return $this->state(function () use ($weeksAgo): array {
            $startsOn = CarbonImmutable::today()->subWeeks($weeksAgo)->subDays(MealPlan::DEFAULT_DAYS - 1);

            return [
                'name' => 'Plan from '.$startsOn->format('j M Y'),
                'starts_on' => $startsOn,
                'ends_on' => $startsOn->addDays(MealPlan::DEFAULT_DAYS - 1),
                'is_active' => false,
            ];
        });
    }

    public function startingOn(CarbonImmutable $startsOn, int $days = MealPlan::DEFAULT_DAYS): self
    {
        return $this->state([
            'starts_on' => $startsOn,
            'ends_on' => $startsOn->addDays($days - 1),
        ]);
    }
}
