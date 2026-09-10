<?php

namespace App\Models;

use App\Services\RankingService;
use Database\Factories\RatingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rating extends Model
{
    /** @use HasFactory<RatingFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'recipe_id',
        'stars',
        'review',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'stars' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (Rating $rating) {
            app(RankingService::class)->updateRecipeStats($rating->recipe_id);
        });

        static::deleted(function (Rating $rating) {
            app(RankingService::class)->updateRecipeStats($rating->recipe_id);
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Recipe, $this>
     */
    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }
}
