<?php

namespace App\Models;

use App\Enums\UnitDimension;
use Database\Factories\IngredientFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * What an ingredient is, as opposed to how one recipe measures it.
 *
 * @property int $id
 * @property string $canonical_name
 * @property ?string $name_bn
 * @property UnitDimension $default_dimension
 * @property bool $is_shoppable
 * @property ?string $preferred_unit
 */
class Ingredient extends Model
{
    /** @use HasFactory<IngredientFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'canonical_name',
        'name_bn',
        'default_dimension',
        'is_shoppable',
        'preferred_unit',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'default_dimension' => UnitDimension::class,
            'is_shoppable' => 'boolean',
        ];
    }

    /**
     * The unit this ingredient is usually written in. Named for the column
     * rather than shortened to unit(), which would shadow the string column of
     * that name on the rows that use one.
     *
     * @return BelongsTo<Unit, $this>
     */
    public function preferredUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'preferred_unit', 'symbol');
    }

    /**
     * @return HasMany<IngredientAlias, $this>
     */
    public function aliases(): HasMany
    {
        return $this->hasMany(IngredientAlias::class);
    }

    /**
     * @return HasMany<RecipeIngredient, $this>
     */
    public function recipeIngredients(): HasMany
    {
        return $this->hasMany(RecipeIngredient::class);
    }
}
