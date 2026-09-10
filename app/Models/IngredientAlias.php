<?php

namespace App\Models;

use Database\Factories\IngredientAliasFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IngredientAlias extends Model
{
    /** @use HasFactory<IngredientAliasFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'ingredient_id',
        'alias',
    ];

    /**
     * @return BelongsTo<Ingredient, $this>
     */
    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }
}
