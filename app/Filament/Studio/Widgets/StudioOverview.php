<?php

namespace App\Filament\Studio\Widgets;

use App\Enums\ModerationStatus;
use App\Filament\Studio\Resources\Recipes\RecipeResource;
use App\Models\Recipe;
use App\Models\RecipeStat;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

/**
 * What one creator has written, and what readers made of it.
 *
 * Every figure here is scoped to the signed-in creator. That is not a display
 * choice — the admin dashboard already carries the site-wide numbers, and a
 * creator seeing the catalogue's totals would be a leak, so each query repeats
 * the ownership filter rather than trusting anything upstream to have applied
 * it. The tab badges in ListRecipes do the same, for the same reason.
 *
 * There is no view tracking anywhere in the app, so nothing here claims to
 * count views. Ratings are the only signal a creator actually has.
 */
class StudioOverview extends StatsOverviewWidget
{
    /**
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        $ownerId = Auth::id();

        if ($ownerId === null) {
            return [];
        }

        $live = $this->countOwn($ownerId, ModerationStatus::Approved);
        $drafts = $this->countOwn($ownerId, ModerationStatus::Draft);
        $takenDown = $this->countOwn($ownerId, ModerationStatus::Unpublished);

        [$ratingsCount, $ratingsAverage] = $this->ratings($ownerId);

        return [
            Stat::make('Live', number_format($live))
                ->description($live === 1 ? 'Recipe on the site' : 'Recipes on the site')
                ->descriptionIcon(Heroicon::OutlinedBookOpen)
                ->descriptionColor('gray')
                ->icon(Heroicon::OutlinedBookOpen)
                ->color($live > 0 ? 'success' : 'gray')
                ->url(RecipeResource::getUrl('index', ['activeTab' => 'live'])),

            Stat::make('Drafts', number_format($drafts))
                ->description($drafts > 0 ? 'Private to you' : 'Nothing half-written')
                ->descriptionIcon($drafts > 0 ? Heroicon::OutlinedPencilSquare : Heroicon::OutlinedCheckCircle)
                ->descriptionColor('gray')
                ->icon(Heroicon::OutlinedPencilSquare)
                ->color($drafts > 0 ? 'info' : 'gray')
                ->url(RecipeResource::getUrl('index', ['activeTab' => 'drafts'])),

            /**
             * Only an admin can take a recipe down, so this is the one figure
             * here a creator cannot change by writing. It stays neutral rather
             * than red: it is information, not a task they can act on.
             */
            Stat::make('Taken down', number_format($takenDown))
                ->description($takenDown > 0 ? 'Removed by a moderator' : 'Nothing removed')
                ->descriptionIcon($takenDown > 0 ? Heroicon::OutlinedEyeSlash : Heroicon::OutlinedCheckCircle)
                ->descriptionColor($takenDown > 0 ? 'warning' : 'gray')
                ->icon(Heroicon::OutlinedEyeSlash)
                ->color('gray')
                ->url(RecipeResource::getUrl('index', ['activeTab' => 'unpublished'])),

            Stat::make('Ratings received', number_format($ratingsCount))
                ->description($ratingsAverage !== null
                    ? number_format($ratingsAverage, 2).' average across your recipes'
                    : 'Nobody has rated your recipes yet')
                ->descriptionIcon(Heroicon::OutlinedStar)
                ->descriptionColor('gray')
                ->icon(Heroicon::OutlinedStar)
                ->color('gray'),
        ];
    }

    protected function countOwn(int $ownerId, ModerationStatus $status): int
    {
        return Recipe::query()
            ->where('recipes.user_id', $ownerId)
            ->where('recipes.moderation_status', $status)
            ->count();
    }

    /**
     * How many ratings this creator's recipes have collected, and what they
     * average.
     *
     * The average is weighted by each recipe's rating count rather than being
     * the mean of the per-recipe averages. Averaging the averages would let a
     * recipe with one five-star rating outweigh one with forty fours, which
     * is the wrong answer to "what do readers make of your cooking".
     *
     * @return array{0: int, 1: float|null}
     */
    protected function ratings(int $ownerId): array
    {
        /** @var object{total: int|string|null, weighted: float|int|string|null}|null $row */
        $row = RecipeStat::query()
            ->join('recipes', 'recipes.id', '=', 'recipe_stats.recipe_id')
            ->where('recipes.user_id', $ownerId)
            ->selectRaw('COALESCE(SUM(recipe_stats.ratings_count), 0) as total')
            ->selectRaw('COALESCE(SUM(recipe_stats.ratings_avg * recipe_stats.ratings_count), 0) as weighted')
            ->first();

        $total = (int) ($row->total ?? 0);

        if ($total < 1) {
            return [0, null];
        }

        return [$total, (float) ($row->weighted ?? 0) / $total];
    }
}
