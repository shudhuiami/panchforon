<?php

namespace App\Filament\Resources\Recipes\Pages;

use App\Enums\ModerationStatus;
use App\Enums\RecipeSource;
use App\Filament\Resources\Recipes\RecipeResource;
use App\Models\Recipe;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListRecipes extends ListRecords
{
    protected static string $resource = RecipeResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        /**
         * "All recipes" is first, and therefore the default, so the landing
         * view is the whole catalogue rather than a filtered slice. The review
         * queue is one tab away and its count is also on the sidebar badge and
         * the dashboard.
         */
        return [
            'all' => Tab::make('All recipes')
                ->badge(Recipe::query()->count()),

            'queue' => Tab::make('Awaiting review')
                ->modifyQueryUsing(fn (Builder $query) => $query->awaitingModeration())
                ->badge(Recipe::query()->awaitingModeration()->count())
                ->badgeColor('warning'),

            'submissions' => Tab::make('User submitted')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('source', RecipeSource::User))
                ->badge(Recipe::query()->where('source', RecipeSource::User)->count()),

            'unpublished' => Tab::make('Unpublished')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('moderation_status', ModerationStatus::Unpublished))
                ->badge(Recipe::query()->where('moderation_status', ModerationStatus::Unpublished)->count()),
        ];
    }
}
