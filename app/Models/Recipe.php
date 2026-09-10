<?php

namespace App\Models;

use App\Enums\RecipeSource;
use Database\Factories\RecipeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property RecipeSource $source
 */
class Recipe extends Model
{
    /** @use HasFactory<RecipeFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'source',
        'external_id',
        'title',
        'slug',
        'cuisine',
        'category',
        'instructions',
        'image_url',
        'servings',
        'source_url',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'source' => RecipeSource::class,
            'servings' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<RecipeIngredient, $this>
     */
    public function ingredients(): HasMany
    {
        return $this->hasMany(RecipeIngredient::class)->orderBy('position');
    }

    /**
     * @return HasMany<Rating, $this>
     */
    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    /**
     * @return HasOne<RecipeStat, $this>
     */
    public function stat(): HasOne
    {
        return $this->hasOne(RecipeStat::class, 'recipe_id');
    }

    /**
     * Scope query to apply filters.
     *
     * @param  Builder<Recipe>  $query
     * @param  array<string, mixed>  $filters
     */
    public function scopeFilter(Builder $query, array $filters): void
    {
        if (! empty($filters['cuisine'])) {
            $query->where('cuisine', $filters['cuisine']);
        }

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (! empty($filters['q'])) {
            $term = '%'.$filters['q'].'%';
            $query->where(function (Builder $sub) use ($term) {
                $sub->where('title', 'like', $term)
                    ->orWhere('instructions', 'like', $term);
            });
        }
    }

    /**
     * Scope query for sorting.
     *
     * @param  Builder<Recipe>  $query
     */
    public function scopeSorted(Builder $query, ?string $sort = 'bayesian'): void
    {
        switch ($sort) {
            case 'rating':
                $query->leftJoin('recipe_stats', 'recipes.id', '=', 'recipe_stats.recipe_id')
                    ->select('recipes.*')
                    ->orderByRaw('COALESCE(recipe_stats.ratings_avg, 0) DESC')
                    ->orderBy('recipes.created_at', 'desc');
                break;

            case 'latest':
                $query->orderBy('recipes.created_at', 'desc');
                break;

            case 'title':
                $query->orderBy('recipes.title', 'asc');
                break;

            case 'bayesian':
            default:
                $query->leftJoin('recipe_stats', 'recipes.id', '=', 'recipe_stats.recipe_id')
                    ->select('recipes.*')
                    ->orderByRaw('COALESCE(recipe_stats.bayesian_score, 0) DESC')
                    ->orderBy('recipes.created_at', 'desc');
                break;
        }
    }
}
