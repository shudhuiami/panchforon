<?php

namespace App\Filament\Resources\Ingredients\Tables;

use App\Enums\UnitDimension;
use App\Filament\Actions\IngredientActions;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class IngredientsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('canonical_name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                TextColumn::make('default_dimension')
                    ->label('Measured by')
                    ->badge()
                    ->formatStateUsing(fn (UnitDimension $state): string => ucfirst($state->value))
                    ->color('gray')
                    ->sortable()
                    ->visibleFrom('md'),

                TextColumn::make('aliases_count')
                    ->label('Also called')
                    ->counts('aliases')
                    ->alignEnd()
                    ->sortable()
                    ->visibleFrom('md'),

                TextColumn::make('recipe_ingredients_count')
                    ->label('Used in')
                    ->counts('recipeIngredients')
                    ->alignEnd()
                    ->sortable(),
            ])
            ->defaultSort('canonical_name')
            ->filters([
                SelectFilter::make('default_dimension')
                    ->label('Measured by')
                    ->options(collect(UnitDimension::cases())->mapWithKeys(fn (UnitDimension $case): array => [$case->value => ucfirst($case->value)])->all()),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    IngredientActions::merge(),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No ingredients yet')
            ->emptyStateDescription('Importing recipes fills this dictionary. It is what lets the shopping list merge two onions into one line.');
    }
}
