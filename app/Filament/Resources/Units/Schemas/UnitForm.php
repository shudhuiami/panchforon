<?php

namespace App\Filament\Resources\Units\Schemas;

use App\Enums\UnitDimension;
use App\Filament\Actions\UnitActions;
use App\Filament\Resources\Units\UnitResource;
use App\Models\Unit;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class UnitForm
{
    /**
     * Two things on this screen are one-way doors, and the copy says so rather
     * than leaving an admin to find out from a wrong shopping list:
     *
     * - the symbol is what every recipe line stores, so it is fixed once the
     *   row exists and the field is read-only on edit;
     * - the factor is how a line is converted before anything is added up, so
     *   saving a new one re-totals every list that uses this unit.
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Callout::make('This is how shopping lists add up')
                    ->description('Every recipe line written in this unit is converted through the factor below before it is merged with anything else. A change here takes effect on the next list anyone builds — including lists for recipes nobody has touched.')
                    ->icon(Heroicon::OutlinedExclamationTriangle)
                    ->color('warning')
                    ->visible(fn (?Unit $record): bool => $record !== null)
                    ->columnSpanFull(),

                Section::make('What this unit means')
                    ->columns(2)
                    ->schema([
                        TextInput::make('symbol')
                            ->label('Symbol')
                            ->required()
                            ->maxLength(20)
                            ->unique(ignoreRecord: true)
                            ->dehydrateStateUsing(fn (string $state): string => mb_strtolower(trim($state)))
                            /**
                             * Recipe lines reference the symbol as text, so renaming one
                             * would orphan every line written in it without a single error.
                             */
                            ->disabled(fn (?Unit $record): bool => $record !== null)
                            ->dehydrated(fn (?Unit $record): bool => $record === null)
                            ->helperText(fn (?Unit $record): string => $record === null
                                ? 'Exactly the text a recipe line is written in: "g", "tbsp", "clove". Lower case.'
                                : 'Fixed. Every recipe line stores this text, so renaming it here would leave those lines pointing at a unit that no longer exists. Add a second unit instead.'),

                        TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(60)
                            ->helperText('How it reads in full: "tablespoon".'),

                        Select::make('dimension')
                            ->label('Measures')
                            ->required()
                            ->native(false)
                            ->live()
                            ->options(UnitResource::measuredDimensionOptions())
                            ->helperText('Weights, volumes and counts are never added to each other. Moving a unit to another dimension moves every line written in it with it.'),

                        TextInput::make('factor_to_canonical')
                            ->label('One of these is')
                            ->required()
                            ->numeric()
                            ->minValue(0.000001)
                            ->step(0.000001)
                            ->suffix(fn (Get $get): string => UnitResource::canonicalSymbol(self::dimensionFrom($get('dimension'))))
                            ->helperText('The conversion itself: one kilogram is 1000 grams, one tablespoon is 14.7868 millilitres. Leave it at 1 for a unit that is already the canonical one.'),

                        TextInput::make('position')
                            ->label('Order')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->maxValue(65535)
                            ->helperText('Where this sits in unit pickers. Lower shows first.'),

                        Placeholder::make('usage')
                            ->label('Currently used by')
                            ->visible(fn (?Unit $record): bool => $record !== null)
                            ->content(fn (Unit $record): string => UnitActions::usageSummary($record)
                                ?? 'Nothing yet, so this unit can still be removed.'),
                    ]),
            ]);
    }

    /**
     * The dimension the form is currently set to, for the conversion suffix.
     * Falls back to weight, which is what an empty form starts out showing.
     */
    private static function dimensionFrom(mixed $state): UnitDimension
    {
        if ($state instanceof UnitDimension) {
            return $state;
        }

        return UnitDimension::tryFrom((string) $state) ?? UnitDimension::Mass;
    }
}
