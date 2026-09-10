<?php

namespace App\Services;

use App\Models\Rating;
use App\Models\Recipe;
use App\Models\RecipeStat;
use Illuminate\Support\Facades\DB;

class RankingService
{
    public const DEFAULT_M = 10;

    public const DEFAULT_GLOBAL_MEAN = 3.8;

    /**
     * Pure Bayesian calculation formula.
     * score = (v / (v + m)) * R + (m / (v + m)) * C
     *
     * @param  int  $v  Rating count for the recipe
     * @param  float  $R  Mean rating for the recipe
     * @param  int  $m  Minimum votes threshold
     * @param  float  $C  Global mean rating across all rated recipes
     */
    public function calculateScore(int $v, float $R, int $m = self::DEFAULT_M, float $C = self::DEFAULT_GLOBAL_MEAN): float
    {
        if ($v <= 0) {
            return $C;
        }

        $denominator = $v + $m;
        if ($denominator === 0) {
            return $C;
        }

        $score = ($v / $denominator) * $R + ($m / $denominator) * $C;

        return round($score, 4);
    }

    /**
     * Calculate global mean rating across all ratings in the system.
     */
    public function calculateGlobalMean(): float
    {
        $avg = DB::table('ratings')->avg('stars');

        return $avg !== null ? round((float) $avg, 4) : self::DEFAULT_GLOBAL_MEAN;
    }

    /**
     * Recompute stats and Bayesian score for a single recipe synchronously.
     */
    public function updateRecipeStats(int $recipeId, ?float $globalMean = null): RecipeStat
    {
        Recipe::findOrFail($recipeId);

        /** @var object{count: int|string|null, avg_stars: int|float|string|null}|null $stats */
        $stats = DB::table('ratings')
            ->where('recipe_id', $recipeId)
            ->selectRaw('COUNT(*) as count, AVG(stars) as avg_stars')
            ->first();

        $count = (int) ($stats->count ?? 0);
        $rawAvg = $stats !== null && $stats->avg_stars !== null ? (float) $stats->avg_stars : null;

        $c = $globalMean ?? $this->calculateGlobalMean();
        $bayesianScore = $this->calculateScore($count, $rawAvg ?? $c, self::DEFAULT_M, $c);

        return RecipeStat::updateOrCreate(
            ['recipe_id' => $recipeId],
            [
                'ratings_count' => $count,
                'ratings_avg' => $rawAvg !== null ? round($rawAvg, 2) : null,
                'bayesian_score' => $bayesianScore,
                'updated_at' => now(),
            ]
        );
    }
}
