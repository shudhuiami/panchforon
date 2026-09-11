<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Enums\ModerationStatus;
use App\Filament\Resources\Recipes\RecipeResource;
use App\Models\Recipe;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Read-only view of a user's submissions. Editing and moderation live on the
 * recipe resource so there is one place where those rules are expressed.
 */
class RecipesRelationManager extends RelationManager
{
    protected static string $relationship = 'recipes';

    protected static ?string $title = 'Recipes';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('moderation_status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (ModerationStatus $state): string => $state->label())
                    ->color(fn (ModerationStatus $state): string => $state->color()),

                TextColumn::make('stat.ratings_avg')
                    ->label('Rating')
                    ->numeric(decimalPlaces: 2)
                    ->placeholder('Unrated')
                    ->alignEnd(),

                TextColumn::make('stat.ratings_count')
                    ->label('Ratings')
                    ->alignEnd()
                    ->placeholder('0'),

                TextColumn::make('created_at')
                    ->label('Submitted')
                    ->date('j M Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([])
            ->recordActions([
                Action::make('open')
                    ->label('Open')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->url(fn (Recipe $record): string => RecipeResource::getUrl('view', ['record' => $record])),
            ])
            ->toolbarActions([])
            ->emptyStateHeading('No recipes yet')
            ->emptyStateDescription('This person has not submitted a recipe.');
    }
}
