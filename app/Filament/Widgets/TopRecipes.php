<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Recipes\RecipeResource;
use App\Models\Recipe;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * What the community actually rates highest, by the same Bayesian score the
 * storefront ranks with, so the panel and the site agree.
 */
class TopRecipes extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Best rated')
            ->description('Ranked the way the storefront ranks, so a single glowing review cannot top the list.')
            ->query(
                Recipe::query()
                    ->publiclyVisible()
                    ->with('stat')
                    ->whereHas('stat', fn (Builder $query) => $query->where('ratings_count', '>', 0))
                    ->sorted('bayesian')
            )
            ->columns([
                TextColumn::make('title')
                    ->wrap()
                    ->weight('medium')
                    ->description(fn (Recipe $record): string => collect([$record->cuisine, $record->category])->filter()->implode(' · '))
                    ->url(fn (Recipe $record): string => RecipeResource::getUrl('view', ['record' => $record])),

                TextColumn::make('stat.bayesian_score')
                    ->label('Score')
                    ->numeric(decimalPlaces: 2)
                    ->alignEnd(),

                TextColumn::make('stat.ratings_avg')
                    ->label('Average')
                    ->numeric(decimalPlaces: 1)
                    ->alignEnd()
                    ->visibleFrom('md'),

                TextColumn::make('stat.ratings_count')
                    ->label('Ratings')
                    ->alignEnd()
                    ->visibleFrom('md'),
            ])
            ->paginationPageOptions([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->emptyStateHeading('Nothing rated yet');
    }
}
