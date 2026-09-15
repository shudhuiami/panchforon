<?php

namespace App\Models;

use Database\Factories\RecipeIngredientFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * How one recipe measures one ingredient. The unit on this row, not the
 * ingredient record, decides what dimension the amount is in.
 *
 * @property ?int $ingredient_id
 * @property ?float $quantity
 * @property ?string $unit
 * @property string $raw_text
 * @property bool $is_optional
 * @property ?string $note
 * @property ?Ingredient $ingredient
 */
class RecipeIngredient extends Model
{
    /** @use HasFactory<RecipeIngredientFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'recipe_id',
        'ingredient_id',
        'quantity',
        'unit',
        'is_optional',
        'note',
        'raw_text',
        'position',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'float',
            'is_optional' => 'boolean',
            'position' => 'integer',
        ];
    }

    /**
     * The units row behind this line's symbol. Not called unit(), which would
     * shadow the unit column itself once the relation were loaded.
     *
     * @return BelongsTo<Unit, $this>
     */
    public function measuredIn(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit', 'symbol');
    }

    /**
     * @return BelongsTo<Recipe, $this>
     */
    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    /**
     * @return BelongsTo<Ingredient, $this>
     */
    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }
}
