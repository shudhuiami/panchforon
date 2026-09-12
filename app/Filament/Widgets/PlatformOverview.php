<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ContentFlags\ContentFlagResource;
use App\Filament\Resources\Recipes\RecipeResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\ContentFlag;
use App\Models\Rating;
use App\Models\Recipe;
use App\Models\User;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Landing figures for the dashboard.
 *
 * Each stat is a single aggregate query, run synchronously on page load. There
 * is no queue on the target hosting, so nothing here is precomputed; at this
 * catalogue size the counts are cheap and every filtered column is indexed.
 */
class PlatformOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $awaitingReview = Recipe::query()->awaitingModeration()->count();
        $ratingsThisWeek = Rating::query()->where('created_at', '>=', now()->subWeek())->count();
        $suspended = User::query()->suspended()->count();
        $openReports = ContentFlag::query()->open()->count();

        return [
            Stat::make('Total users', number_format(User::query()->count()))
                ->description($suspended > 0 ? "{$suspended} suspended" : 'None suspended')
                ->descriptionIcon($suspended > 0 ? Heroicon::OutlinedNoSymbol : Heroicon::OutlinedCheckCircle)
                ->descriptionColor($suspended > 0 ? 'danger' : 'gray')
                ->icon(Heroicon::OutlinedUsers)
                ->color('gray')
                ->url(UserResource::getUrl('index')),

            Stat::make('Total recipes', number_format(Recipe::query()->count()))
                ->description('Imported and submitted')
                ->descriptionIcon(Heroicon::OutlinedBookOpen)
                ->descriptionColor('gray')
                ->icon(Heroicon::OutlinedBookOpen)
                ->color('gray')
                ->url(RecipeResource::getUrl('index')),

            Stat::make('Awaiting moderation', number_format($awaitingReview))
                ->description($awaitingReview > 0 ? 'Needs a decision' : 'Queue is clear')
                ->descriptionIcon($awaitingReview > 0 ? Heroicon::OutlinedClock : Heroicon::OutlinedCheckCircle)
                ->descriptionColor($awaitingReview > 0 ? 'warning' : 'success')
                ->icon(Heroicon::OutlinedClipboardDocumentCheck)
                ->color($awaitingReview > 0 ? 'warning' : 'gray')
                ->url(RecipeResource::getUrl('index', ['activeTab' => 'queue'])),

            Stat::make('Open reports', number_format($openReports))
                ->description($openReports > 0 ? 'Reported by the community' : 'Nothing reported')
                ->descriptionIcon($openReports > 0 ? Heroicon::OutlinedFlag : Heroicon::OutlinedCheckCircle)
                ->descriptionColor($openReports > 0 ? 'danger' : 'success')
                ->icon(Heroicon::OutlinedFlag)
                ->color($openReports > 0 ? 'danger' : 'gray')
                ->url(ContentFlagResource::getUrl('index')),

            /**
             * Deliberately neutral. Only the queues turn amber or red, so a
             * colour on this row always means something needs attention.
             */
            Stat::make('Ratings this week', number_format($ratingsThisWeek))
                ->description('Last seven days')
                ->descriptionIcon(Heroicon::OutlinedStar)
                ->descriptionColor('gray')
                ->icon(Heroicon::OutlinedStar)
                ->color('gray'),
        ];
    }
}
