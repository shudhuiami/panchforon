<?php

namespace App\Filament\Studio\Resources\Recipes\Tables;

use App\Enums\ModerationStatus;
use App\Enums\SpiceLevel;
use App\Filament\Studio\Resources\Recipes\RecipeResource;
use App\Models\Recipe;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * One creator's recipes. The rows are already narrowed to them by
 * RecipeResource::getEloquentQuery(), so nothing here filters by author and
 * there is no author column: every row has the same one.
 */
class RecipesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->weight('medium')
                    /**
                     * Carries the cuisine and category on phones, where both
                     * of those columns are hidden.
                     */
                    ->description(fn (Recipe $record): string => collect([
                        $record->cuisine,
                        $record->category,
                    ])->filter()->implode(' · ')),

                TextColumn::make('moderation_status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (ModerationStatus $state): string => $state->label())
                    ->color(fn (ModerationStatus $state): string => $state->color())
                    ->icon(fn (ModerationStatus $state): string => $state->icon())
                    ->sortable(),

                TextColumn::make('cuisine')
                    ->placeholder('-')
                    ->sortable()
                    ->toggleable()
                    ->visibleFrom('lg'),

                TextColumn::make('category')
                    ->placeholder('-')
                    ->sortable()
                    ->toggleable()
                    ->visibleFrom('lg'),

                /**
                 * Prep and cook are two columns nobody scans; what a cook is
                 * choosing on is the sum.
                 */
                TextColumn::make('total_minutes')
                    ->label('Time')
                    ->state(function (Recipe $record): ?string {
                        $total = RecipeResource::totalMinutes($record);

                        return $total === null ? null : "{$total} min";
                    })
                    ->alignEnd()
                    ->placeholder('-')
                    ->toggleable()
                    ->visibleFrom('xl'),

                TextColumn::make('spice_level')
                    ->label('Spice')
                    ->badge()
                    ->formatStateUsing(fn (SpiceLevel $state): string => $state->label())
                    ->color(fn (SpiceLevel $state): string => $state->color())
                    ->placeholder('-')
                    ->sortable()
                    ->toggleable()
                    ->visibleFrom('xl'),

                TextColumn::make('stat.ratings_avg')
                    ->label('Rating')
                    ->numeric(decimalPlaces: 2)
                    ->placeholder('Unrated')
                    ->alignEnd()
                    ->sortable()
                    ->visibleFrom('md'),

                TextColumn::make('stat.ratings_count')
                    ->label('Ratings')
                    ->alignEnd()
                    ->placeholder('0')
                    ->sortable()
                    ->visibleFrom('lg'),

                TextColumn::make('created_at')
                    ->label('Added')
                    ->date('j M Y')
                    ->sortable()
                    ->toggleable()
                    ->visibleFrom('md'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('moderation_status')
                    ->label('Status')
                    ->options(collect(ModerationStatus::cases())
                        ->mapWithKeys(fn (ModerationStatus $status): array => [$status->value => $status->label()])
                        ->all()),

                SelectFilter::make('spice_level')
                    ->label('Spice level')
                    ->options(collect(SpiceLevel::cases())
                        ->mapWithKeys(fn (SpiceLevel $level): array => [$level->value => $level->label()])
                        ->all()),
            ])
            /**
             * Read and edit your own. Approving and unpublishing are a
             * moderator's job and stay in the admin panel.
             *
             * Deliberately no bulk actions, delete above all: Filament asks
             * RecipePolicy::deleteAny() whether to offer one, and that question
             * comes with no record to check ownership against, so a bulk delete
             * cannot be scoped to the rows a creator owns. Deleting one recipe
             * at a time goes through delete(), which can be.
             */
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->emptyStateHeading('No recipes yet')
            ->emptyStateDescription('Recipes you write will show up here.');
    }
}
