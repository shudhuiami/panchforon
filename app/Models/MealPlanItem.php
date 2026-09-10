<?php

namespace App\Models;

use Database\Factories\MealPlanItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MealPlanItem extends Model
{
    /** @use HasFactory<MealPlanItemFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'meal_plan_id',
        'recipe_id',
        'servings',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'servings' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<MealPlan, $this>
     */
    public function mealPlan(): BelongsTo
    {
        return $this->belongsTo(MealPlan::class);
    }

    /**
     * @return BelongsTo<Recipe, $this>
     */
    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }
}
