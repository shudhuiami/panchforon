<?php

namespace App\Services;

use App\Enums\ModerationStatus;
use App\Http\Resources\RecipeListResource;
use App\Models\Rating;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * Everything the storefront home page renders, assembled here so the page
 * costs a single request. The result is cached and forgotten whenever a
 * recipe or its stats change, so moderation and fresh ratings show at once.
 */
class HomeFeed
{
    public const CACHE_KEY = 'api.home-feed';

    private const TTL_SECONDS = 600;

    /**
     * @return array<string, mixed>
     */
    public function cached(): array
    {
        return Cache::remember(self::CACHE_KEY, self::TTL_SECONDS, fn (): array => $this->build());
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $topRated = Recipe::query()->with('stat')->publiclyVisible()->sorted('bayesian')->limit(8)->get();
        $latest = Recipe::query()->with('stat')->publiclyVisible()->sorted('latest')->limit(4)->get();
        $featured = $topRated->first(fn (Recipe $recipe): bool => $recipe->image_url !== null) ?? $topRated->first();
        $cuisines = $this->cuisines();

        return [
            'stats' => [
                'recipes' => Recipe::query()->publiclyVisible()->count(),
                'cuisines' => count($cuisines),
                'ratings' => Rating::query()
                    ->join('recipes', 'recipes.id', '=', 'ratings.recipe_id')
                    ->whereIn('recipes.moderation_status', ModerationStatus::publiclyVisible())
                    ->count(),
                'cooks' => User::query()->whereNull('suspended_at')->count(),
            ],
            'featured' => $featured !== null ? (new RecipeListResource($featured))->resolve() : null,
            'top_rated' => RecipeListResource::collection($topRated)->resolve(),
            'latest' => RecipeListResource::collection($latest)->resolve(),
            'cuisines' => $cuisines,
        ];
    }

    /**
     * Cuisines by size, each with the photo of its best-ranked recipe.
     *
     * @return list<array{cuisine: string, count: int, image_url: string|null}>
     */
    private function cuisines(): array
    {
        $counts = Recipe::query()
            ->publiclyVisible()
            ->whereNotNull('cuisine')
            ->where('cuisine', '!=', '')
            ->selectRaw('cuisine, count(*) as count')
            ->groupBy('cuisine')
            ->orderByDesc('count')
            ->orderBy('cuisine')
            ->limit(12)
            ->toBase()
            ->get();

        $images = Recipe::query()
            ->publiclyVisible()
            ->whereIn('recipes.cuisine', $counts->pluck('cuisine')->all())
            ->whereNotNull('recipes.image_url')
            ->sorted('bayesian')
            ->limit(300)
            ->get()
            ->unique('cuisine')
            ->pluck('image_url', 'cuisine');

        return $counts->map(fn (object $row): array => [
            'cuisine' => (string) $row->cuisine,
            'count' => (int) $row->count,
            'image_url' => $images->get((string) $row->cuisine),
        ])->values()->all();
    }
}
