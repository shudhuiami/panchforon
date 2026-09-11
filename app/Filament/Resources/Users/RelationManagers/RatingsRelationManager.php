<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Filament\Resources\Recipes\RecipeResource;
use App\Models\Rating;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Read-only view of the ratings a user has left, so an admin reviewing a
 * report can see the pattern of someone's reviews in one place.
 */
class RatingsRelationManager extends RelationManager
{
    protected static string $relationship = 'ratings';

    protected static ?string $title = 'Ratings';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('stars')
            ->columns([
                TextColumn::make('recipe.title')
                    ->label('Recipe')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('stars')
                    ->label('Stars')
                    ->badge()
                    ->formatStateUsing(fn (int $state): string => str_repeat('★', $state))
                    ->color(fn (int $state): string => match (true) {
                        $state >= 4 => 'success',
                        $state === 3 => 'warning',
                        default => 'danger',
                    })
                    ->sortable(),

                TextColumn::make('review')
                    ->label('Review')
                    ->limit(80)
                    ->placeholder('No written review')
                    ->wrap(),

                TextColumn::make('created_at')
                    ->label('Left')
                    ->date('j M Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([])
            ->recordActions([
                Action::make('openRecipe')
                    ->label('Open recipe')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->url(fn (Rating $record): string => RecipeResource::getUrl('view', ['record' => $record->recipe_id])),
            ])
            ->toolbarActions([])
            ->emptyStateHeading('No ratings yet')
            ->emptyStateDescription('This person has not rated a recipe.');
    }
}
