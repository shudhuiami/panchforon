<?php

namespace App\Models;

use App\Services\HomeFeed;
use Database\Factories\RecipeStatFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecipeStat extends Model
{
    /** @use HasFactory<RecipeStatFactory> */
    use HasFactory;

    /**
     * @var string
     */
    protected $primaryKey = 'recipe_id';

    /**
     * @var bool
     */
    public $incrementing = false;

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'recipe_id',
        'ratings_count',
        'ratings_avg',
        'bayesian_score',
        'updated_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'recipe_id' => 'integer',
            'ratings_count' => 'integer',
            'ratings_avg' => 'float',
            'bayesian_score' => 'float',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * The home feed is cached; any change here must show up on the next request.
     */
    protected static function booted(): void
    {
        static::saved(fn () => HomeFeed::forget());
        static::deleted(fn () => HomeFeed::forget());
    }

    /**
     * @return BelongsTo<Recipe, $this>
     */
    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }
}
