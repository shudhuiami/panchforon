<?php

namespace App\Filament\Resources\Ingredients\Schemas;

use App\Filament\Resources\Units\UnitResource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class IngredientForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        TextInput::make('canonical_name')
                            ->label('Name')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText('Lower case, as it should read on a shopping list.')
                            ->dehydrateStateUsing(fn (string $state): string => mb_strtolower(trim($state))),

                        TextInput::make('name_bn')
                            ->label('Bengali name')
                            ->maxLength(255)
                            ->helperText('Optional. Shown next to the English name where both are known.'),

                        Select::make('default_dimension')
                            ->label('Measured by')
                            ->required()
                            ->options(UnitResource::dimensionOptions())
                            ->helperText('Only a fallback, for lines written with no unit at all. A line that has a unit is measured by that unit.'),

                        Select::make('preferred_unit')
                            ->label('Usually written in')
                            ->options(UnitResource::unitOptionsByDimension())
                            ->searchable()
                            ->placeholder('No preference')
                            ->helperText('A suggestion for new recipe lines, and how this ingredient reads on its own. It does not force anything: the unit on a line still decides how that line merges.'),
                    ])
                    ->columns(2),

                Section::make('Shopping')
                    ->schema([
                        Toggle::make('is_shoppable')
                            ->label('Put this on shopping lists')
                            ->default(true)
                            ->helperText('Turn this off for things nobody buys. Water is a real ingredient and belongs in the method, but a shopping list that says "500 ml water" is wrong. An ingredient with this off never reaches a list, however many recipes use it.'),
                    ]),
            ]);
    }
}
