<?php

namespace App\Models;

use App\Enums\UnitDimension;
use Database\Factories\IngredientFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $canonical_name
 * @property UnitDimension $default_dimension
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
        'default_dimension',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'default_dimension' => UnitDimension::class,
        ];
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
