<?php

namespace App\Services;

use App\Models\ContentFlag;
use App\Models\Rating;
use App\Models\Recipe;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * The numbers behind the reports screen.
 *
 * Buckets are counted in PHP rather than with a database date function,
 * because the date expressions differ between SQLite and MySQL and this has
 * to behave the same on a developer machine and on the shared host. Only the
 * timestamps inside the window are read, and at this catalogue size that is a
 * few thousand rows at most; a larger site would want a grouped query per
 * driver instead.
 *
 * @phpstan-type Bucket array{label: string, start: CarbonImmutable, end: CarbonImmutable}
 */
class GrowthReport
{
    /**
     * Signups, recipes and ratings per week over the given number of weeks.
     *
     * @return array{labels: list<string>, users: list<int>, recipes: list<int>, ratings: list<int>}
     */
    public function weekly(int $weeks = 12): array
    {
        $buckets = $this->weekBuckets($weeks);
        $since = $buckets[0]['start'];

        return [
            'labels' => array_column($buckets, 'label'),
            'users' => $this->bucketCounts(User::query(), $since, $buckets),
            'recipes' => $this->bucketCounts(Recipe::query(), $since, $buckets),
            'ratings' => $this->bucketCounts(Rating::query(), $since, $buckets),
        ];
    }

    /**
     * The largest cuisines in the public catalogue.
     *
     * @return array{labels: list<string>, counts: list<int>}
     */
    public function cuisineBreakdown(int $limit = 8): array
    {
        $rows = Recipe::query()
            ->publiclyVisible()
            ->whereNotNull('cuisine')
            ->where('cuisine', '!=', '')
            ->selectRaw('cuisine, count(*) as aggregate')
            ->groupBy('cuisine')
            ->orderByDesc('aggregate')
            ->orderBy('cuisine')
            ->limit($limit)
            ->toBase()
            ->get();

        return [
            'labels' => $rows->pluck('cuisine')->map(fn (mixed $name): string => (string) $name)->all(),
            'counts' => $rows->pluck('aggregate')->map(fn (mixed $count): int => (int) $count)->all(),
        ];
    }

    /**
     * Headline figures for the reports screen.
     *
     * @return array{new_users: int, new_recipes: int, new_ratings: int, open_flags: int, unrated_share: float}
     */
    public function lastThirtyDays(): array
    {
        $since = now()->subDays(30);
        $published = Recipe::query()->publiclyVisible()->count();
        $unrated = Recipe::query()->publiclyVisible()->whereDoesntHave('ratings')->count();

        return [
            'new_users' => User::query()->where('created_at', '>=', $since)->count(),
            'new_recipes' => Recipe::query()->where('created_at', '>=', $since)->count(),
            'new_ratings' => Rating::query()->where('created_at', '>=', $since)->count(),
            'open_flags' => ContentFlag::query()->open()->count(),
            'unrated_share' => $published > 0 ? round($unrated / $published * 100, 1) : 0.0,
        ];
    }

    /**
     * @param  Builder<covariant \Illuminate\Database\Eloquent\Model>  $query
     * @param  list<Bucket>  $buckets
     * @return list<int>
     */
    private function bucketCounts(Builder $query, CarbonImmutable $since, array $buckets): array
    {
        /** @var Collection<int, Carbon> $timestamps */
        $timestamps = $query->where('created_at', '>=', $since)->pluck('created_at');

        return array_map(
            fn (array $bucket): int => $timestamps
                ->filter(fn (Carbon $at): bool => $at >= $bucket['start'] && $at < $bucket['end'])
                ->count(),
            $buckets,
        );
    }

    /**
     * Consecutive weeks ending with the one in progress.
     *
     * @return list<Bucket>
     */
    private function weekBuckets(int $weeks): array
    {
        $thisWeek = CarbonImmutable::now()->startOfWeek();
        $buckets = [];

        for ($offset = $weeks - 1; $offset >= 0; $offset--) {
            $start = $thisWeek->subWeeks($offset);

            $buckets[] = [
                'label' => $start->format('j M'),
                'start' => $start,
                'end' => $start->addWeek(),
            ];
        }

        return $buckets;
    }
}
