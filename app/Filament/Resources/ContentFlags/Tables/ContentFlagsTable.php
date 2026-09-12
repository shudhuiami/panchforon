<?php

namespace App\Filament\Resources\ContentFlags\Tables;

use App\Enums\FlagReason;
use App\Enums\FlagStatus;
use App\Filament\Actions\ContentFlagActions;
use App\Filament\Resources\Recipes\RecipeResource;
use App\Models\ContentFlag;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ContentFlagsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('recipe.title')
                    ->label('Recipe')
                    ->searchable()
                    ->wrap()
                    ->weight('medium')
                    /**
                     * Carries the reason and reporter on phones, where both
                     * columns are hidden.
                     */
                    ->description(fn (ContentFlag $record): string => collect([
                        $record->reason->label(),
                        $record->reporter?->name,
                    ])->filter()->implode(' · '))
                    ->url(fn (ContentFlag $record): string => RecipeResource::getUrl('view', ['record' => $record->recipe])),

                TextColumn::make('reason')
                    ->label('Reason')
                    ->badge()
                    ->formatStateUsing(fn (FlagReason $state): string => $state->label())
                    ->color(fn (FlagReason $state): string => $state->color())
                    ->sortable()
                    ->visibleFrom('md'),

                TextColumn::make('note')
                    ->label('What they said')
                    ->wrap()
                    ->limit(90)
                    ->placeholder('No note')
                    ->toggleable()
                    ->visibleFrom('xl'),

                TextColumn::make('reporter.name')
                    ->label('Reported by')
                    ->searchable()
                    ->placeholder('Account closed')
                    ->toggleable()
                    ->visibleFrom('lg'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (FlagStatus $state): string => $state->label())
                    ->color(fn (FlagStatus $state): string => $state->color())
                    ->icon(fn (FlagStatus $state): string => $state->icon())
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Reported')
                    ->since()
                    ->sortable()
                    ->visibleFrom('md'),

                TextColumn::make('reviewer.name')
                    ->label('Decided by')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('reason')
                    ->options(FlagReason::options()),

                SelectFilter::make('status')
                    ->options(collect(FlagStatus::cases())->mapWithKeys(fn (FlagStatus $case): array => [$case->value => $case->label()])->all()),
            ])
            ->recordActions([
                ActionGroup::make([
                    ContentFlagActions::unpublishRecipe(),
                    ContentFlagActions::markActioned(),
                    ContentFlagActions::dismiss(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ContentFlagActions::dismissBulk(),
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Nothing reported')
            ->emptyStateDescription('When someone reports a recipe it lands here for a decision.');
    }
}
