<?php

namespace App\Filament\Resources\Recipes\Tables;

use App\Enums\ModerationStatus;
use App\Enums\RecipeSource;
use App\Filament\Actions\RecipeModerationActions;
use App\Models\Recipe;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

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
                     * Carries the source on phones, where that column is hidden.
                     */
                    ->description(fn (Recipe $record): string => collect([
                        $record->source === RecipeSource::Api ? 'TheMealDB' : 'User',
                        $record->cuisine,
                        $record->category,
                    ])->filter()->implode(' · ')),

                TextColumn::make('source')
                    ->label('Source')
                    ->badge()
                    ->formatStateUsing(fn (RecipeSource $state): string => $state === RecipeSource::Api ? 'TheMealDB' : 'User')
                    ->color(fn (RecipeSource $state): string => $state === RecipeSource::Api ? 'gray' : 'primary')
                    ->sortable()
                    ->visibleFrom('md'),

                TextColumn::make('user.name')
                    ->label('Author')
                    ->searchable()
                    ->placeholder('Imported')
                    ->toggleable()
                    ->visibleFrom('lg'),

                TextColumn::make('moderation_status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (ModerationStatus $state): string => $state->label())
                    ->color(fn (ModerationStatus $state): string => $state->color())
                    ->icon(fn (ModerationStatus $state): string => $state->icon())
                    ->sortable(),

                TextColumn::make('stat.ratings_avg')
                    ->label('Rating')
                    ->numeric(decimalPlaces: 2)
                    ->placeholder('Unrated')
                    ->alignEnd()
                    ->sortable()
                    ->visibleFrom('lg'),

                TextColumn::make('stat.ratings_count')
                    ->label('Ratings')
                    ->alignEnd()
                    ->placeholder('0')
                    ->sortable()
                    ->visibleFrom('xl'),

                TextColumn::make('created_at')
                    ->label('Added')
                    ->date('j M Y')
                    ->sortable()
                    ->toggleable()
                    ->visibleFrom('xl'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('source')
                    ->label('Source')
                    ->options([
                        RecipeSource::User->value => 'User submitted',
                        RecipeSource::Api->value => 'TheMealDB import',
                    ]),

                SelectFilter::make('moderation_status')
                    ->label('Status')
                    ->options(collect(ModerationStatus::cases())
                        ->mapWithKeys(fn (ModerationStatus $status): array => [$status->value => $status->label()])
                        ->all()),

                SelectFilter::make('cuisine')
                    ->label('Cuisine')
                    ->options(fn (): array => Recipe::query()
                        ->whereNotNull('cuisine')
                        ->where('cuisine', '!=', '')
                        ->distinct()
                        ->orderBy('cuisine')
                        ->pluck('cuisine', 'cuisine')
                        ->all())
                    ->searchable(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    RecipeModerationActions::approve(),
                    RecipeModerationActions::unpublish(),
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    RecipeModerationActions::approveBulk(),
                    RecipeModerationActions::unpublishBulk(),
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No recipes')
            ->emptyStateDescription('Import from TheMealDB or wait for a submission.');
    }
}
