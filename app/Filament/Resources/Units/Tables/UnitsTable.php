<?php

namespace App\Filament\Resources\Units\Tables;

use App\Enums\UnitDimension;
use App\Filament\Actions\UnitActions;
use App\Filament\Resources\Units\UnitResource;
use App\Models\Unit;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UnitsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('symbol')
                    ->label('Symbol')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->description(fn (Unit $record): string => $record->name),

                TextColumn::make('dimension')
                    ->label('Measures')
                    ->badge()
                    ->formatStateUsing(fn (UnitDimension $state): string => UnitResource::dimensionLabel($state))
                    ->color('gray')
                    ->sortable()
                    ->visibleFrom('md'),

                TextColumn::make('factor_to_canonical')
                    ->label('One of these is')
                    ->formatStateUsing(fn (float $state, Unit $record): string => self::readableFactor($state).' '.UnitResource::canonicalSymbol($record->dimension))
                    ->alignEnd()
                    ->sortable(),

                /**
                 * The stake in editing the row next to it: this is how many
                 * recipe lines change meaning when the factor changes.
                 */
                TextColumn::make('recipe_lines_count')
                    ->label('Recipe lines')
                    ->numeric()
                    ->alignEnd()
                    ->sortable()
                    ->placeholder('0')
                    ->visibleFrom('md'),

                TextColumn::make('position')
                    ->label('Order')
                    ->alignEnd()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('position')
            ->filters([
                SelectFilter::make('dimension')
                    ->label('Measures')
                    ->options(UnitResource::measuredDimensionOptions()),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    UnitActions::delete(),
                ]),
            ])
            /**
             * No bulk delete: these are fourteen reference rows the merge
             * engine reads on every shopping list, and each one has to be
             * checked for recipe lines before it can go.
             */
            ->paginated(false)
            ->emptyStateHeading('No units')
            ->emptyStateDescription('The units table ships with the install. If it is empty, no shopping list can convert or merge anything.');
    }

    /**
     * Factors are stored to six decimal places, which reads badly for the
     * whole-number ones: 1000.000000 rather than 1,000.
     */
    private static function readableFactor(float $factor): string
    {
        if ($factor === floor($factor)) {
            return number_format($factor);
        }

        return rtrim(rtrim(number_format($factor, 6, '.', ','), '0'), '.');
    }
}
