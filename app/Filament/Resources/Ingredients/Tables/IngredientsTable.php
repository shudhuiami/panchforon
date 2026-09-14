<?php

namespace App\Filament\Resources\Ingredients\Tables;

use App\Enums\UnitDimension;
use App\Filament\Actions\IngredientActions;
use App\Filament\Resources\Units\UnitResource;
use App\Models\Ingredient;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
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
                    ->weight('medium')
                    ->description(fn (Ingredient $record): ?string => $record->name_bn),

                /**
                 * Worth its own column rather than a detail on the edit form:
                 * an ingredient switched off here disappears from every
                 * shopping list without anything else changing, so it has to be
                 * visible from the list.
                 */
                IconColumn::make('is_shoppable')
                    ->label('Shopping list')
                    ->boolean()
                    ->sortable()
                    ->tooltip(fn (Ingredient $record): string => $record->is_shoppable
                        ? 'Appears on shopping lists.'
                        : 'Never reaches a shopping list.'),

                TextColumn::make('default_dimension')
                    ->label('Measured by')
                    ->badge()
                    ->formatStateUsing(fn (UnitDimension $state): string => UnitResource::dimensionLabel($state))
                    ->color('gray')
                    ->sortable()
                    ->visibleFrom('md'),

                TextColumn::make('preferred_unit')
                    ->label('Usually in')
                    ->placeholder('-')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

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
                TernaryFilter::make('is_shoppable')
                    ->label('Shopping list')
                    ->placeholder('Everything')
                    ->trueLabel('Goes on lists')
                    ->falseLabel('Never on a list'),

                SelectFilter::make('default_dimension')
                    ->label('Measured by')
                    ->options(UnitResource::dimensionOptions()),
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
