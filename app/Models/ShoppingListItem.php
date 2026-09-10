<?php

namespace App\Models;

use Database\Factories\ShoppingListItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShoppingListItem extends Model
{
    /** @use HasFactory<ShoppingListItemFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'meal_plan_id',
        'ingredient_id',
        'display_name',
        'quantity',
        'unit',
        'is_unmerged',
        'source_note',
        'is_checked',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'float',
            'is_unmerged' => 'boolean',
            'is_checked' => 'boolean',
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
     * @return BelongsTo<Ingredient, $this>
     */
    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }
}
