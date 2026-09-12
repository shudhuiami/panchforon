<?php

namespace App\Filament\Resources\Ingredients\Schemas;

use App\Enums\UnitDimension;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
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

                        Select::make('default_dimension')
                            ->label('Measured by')
                            ->required()
                            ->options(collect(UnitDimension::cases())->mapWithKeys(fn (UnitDimension $case): array => [$case->value => ucfirst($case->value)])->all())
                            ->helperText('What the shopping list may add up. Weights never merge with volumes.'),
                    ])
                    ->columns(2),
            ]);
    }
}
