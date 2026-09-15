<?php

namespace App\Filament\Resources\Recipes\Schemas;

use App\Enums\SpiceLevel;
use App\Models\Recipe;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RecipeForm
{
    /**
     * Editing is for correcting a submission before publishing it, so the
     * moderation state is not part of this form: it is changed through the
     * approve/unpublish actions, which also record who acted and when.
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Recipe')
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        TextInput::make('name_bn')
                            ->label('Bengali title')
                            ->maxLength(255)
                            ->columnSpanFull()
                            ->helperText('Optional. Shown under the English title where it is known.'),

                        TextInput::make('cuisine')
                            ->maxLength(100)
                            ->datalist(fn (): array => self::distinctValues('cuisine')),

                        TextInput::make('category')
                            ->maxLength(100)
                            ->datalist(fn (): array => self::distinctValues('category')),

                        TextInput::make('servings')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(100)
                            ->required(),

                        Select::make('user_id')
                            ->label('Author')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->placeholder('Imported from TheMealDB')
                            ->disabled()
                            ->dehydrated(false),

                        Textarea::make('instructions')
                            ->required()
                            ->rows(12)
                            ->columnSpanFull(),
                    ]),

                /**
                 * Minutes, never "40–50 mins": these are meant to be filtered
                 * on, summed into a meal plan and compared, none of which a
                 * range written as text supports.
                 */
                Section::make('Timing and heat')
                    ->columns(3)
                    ->schema([
                        TextInput::make('prep_minutes')
                            ->label('Prep')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(65535)
                            ->suffix('minutes')
                            ->helperText('Chopping, marinating, anything before the heat goes on.'),

                        TextInput::make('cook_minutes')
                            ->label('Cook')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(65535)
                            ->suffix('minutes')
                            ->helperText('One number. A range becomes its midpoint.'),

                        Select::make('spice_level')
                            ->label('Spice level')
                            ->native(false)
                            ->placeholder('Not stated')
                            ->options(collect(SpiceLevel::cases())
                                ->mapWithKeys(fn (SpiceLevel $level): array => [$level->value => $level->label()])
                                ->all())
                            ->helperText('Leave it unset for dishes where heat is not the point.'),
                    ]),

                Section::make('Links')
                    ->columns(2)
                    ->collapsed()
                    ->schema([
                        TextInput::make('image_url')
                            ->label('Image URL')
                            ->url()
                            ->maxLength(2048),

                        TextInput::make('source_url')
                            ->label('Source URL')
                            ->url()
                            ->maxLength(2048),
                    ]),
            ]);
    }

    /**
     * @return array<int, string>
     */
    private static function distinctValues(string $column): array
    {
        return Recipe::query()
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->distinct()
            ->orderBy($column)
            ->pluck($column)
            ->all();
    }
}
