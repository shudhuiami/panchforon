<?php

namespace App\Filament\Studio\Resources\Recipes\Schemas;

use App\Enums\SpiceLevel;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\Unit;
use App\Rules\YouTubeVideoLink;
use App\Services\RecipeIngredientWriter;
use App\Support\YouTubeVideoId;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Writing a recipe in the Creator Studio.
 *
 * The admin panel's RecipeForm is the shape this follows, with three things
 * taken out or put in:
 *
 * - No author. The admin's is a disabled Select showing whose submission is
 *   being corrected; here it is always the person typing, so a field that can
 *   only ever say one thing is a field that earns nothing.
 * - No moderation status, for the same reason the admin form has none: the
 *   state is changed through actions that also record who acted and when, and
 *   a creator does not get to set it at all. What they do get is the draft
 *   toggle below, which is a question about this save rather than a status.
 * - A YouTube field, which the admin form has not got, because a creator
 *   pasting the link to their own video is the case it exists for.
 *
 * And the ingredients repeater, which is new to both: the admin panel has
 * never been able to edit an ingredient list, and a recipe you cannot write
 * ingredients into is not a recipe.
 */
class RecipeForm
{
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
                            ->required()
                            ->default(4),

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
                            ->maxValue(1440)
                            ->suffix('minutes')
                            ->helperText('Chopping, marinating, anything before the heat goes on.'),

                        TextInput::make('cook_minutes')
                            ->label('Cook')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(1440)
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

                self::ingredients(),

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

                        self::youTubeLink(),
                    ]),

                Section::make('Publishing')
                    ->schema([
                        /**
                         * A question about this save, not a status: the status
                         * itself is worked out by ModerationStatus::forAuthor()
                         * when the record is created, and never shown here.
                         *
                         * Create only. Once a recipe exists, moving it between
                         * draft and live is a change of state rather than a
                         * change of content, and belongs to an action.
                         */
                        Toggle::make('keep_as_draft')
                            ->label('Keep this as a draft')
                            ->helperText('Off publishes it to the site as soon as you save. On keeps it private to you.')
                            ->default(false)
                            ->visibleOn('create'),
                    ])
                    ->visibleOn('create'),
            ]);
    }

    /**
     * What the recipe is made of.
     *
     * A row is written the way a person says it — "2 tbsp mustard oil" split
     * across three boxes — and resolved to a canonical ingredient on save by
     * RecipeIngredientWriter, the same class the API writes its rows with. The
     * name box is therefore not a column: recipe_ingredients stores an
     * ingredient_id and the raw line, and the writer works out both.
     */
    protected static function ingredients(): Section
    {
        return Section::make('Ingredients')
            ->description('One line per ingredient, in the order a cook needs them.')
            ->schema([
                Repeater::make('ingredients')
                    ->hiddenLabel()
                    ->relationship()
                    /**
                     * Drag order is the ingredient order. relationship() turns
                     * reordering off by default, so both of these are needed:
                     * one to re-enable it, one to say where it is kept.
                     */
                    ->reorderable()
                    ->orderColumn('position')
                    ->addActionLabel('Add an ingredient')
                    ->defaultItems(1)
                    ->minItems(1)
                    ->columns(12)
                    ->itemLabel(fn (array $state): ?string => self::ingredientLabel($state))
                    ->schema([
                        TextInput::make('quantity')
                            ->numeric()
                            ->minValue(0)
                            ->columnSpan(2)
                            ->placeholder('2'),

                        TextInput::make('unit')
                            ->maxLength(50)
                            ->columnSpan(2)
                            ->placeholder('tbsp')
                            /**
                             * A suggestion list rather than a Select, and the
                             * column is never validated against it, for the
                             * reason StoreRecipeRequest gives: a line whose
                             * unit does not resolve ("a handful") is a line
                             * that will not merge into a shopping list, not a
                             * reason to reject the recipe.
                             */
                            ->datalist(fn (): array => self::unitSymbols()),

                        TextInput::make('name')
                            ->label('Ingredient')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(4)
                            ->placeholder('mustard oil')
                            ->datalist(fn (): array => self::ingredientNames()),

                        TextInput::make('note')
                            ->maxLength(255)
                            ->columnSpan(3)
                            ->placeholder('finely chopped'),

                        Toggle::make('is_optional')
                            ->label('Optional')
                            ->inline(false)
                            ->columnSpan(1),
                    ])
                    /**
                     * The name box holds no column of its own, so on the way in
                     * it is read back off the canonical ingredient the row was
                     * resolved to, falling back to the line as it was written
                     * when there is no canonical match — an imported row, say.
                     */
                    ->mutateRelationshipDataBeforeFillUsing(
                        fn (array $data): array => self::fillRow($data),
                    )
                    ->mutateRelationshipDataBeforeCreateUsing(
                        fn (array $data): array => self::rowAttributes($data),
                    )
                    ->mutateRelationshipDataBeforeSaveUsing(
                        fn (array $data): array => self::rowAttributes($data),
                    ),
            ]);
    }

    /**
     * A pasted link in, a bare video id in the column.
     *
     * Validated with the same rule the API uses, so what the studio accepts and
     * what the API accepts cannot drift. The rule rewrites the value on the
     * validator it was given, which a form request reads back and a Livewire
     * component does not, so the normalisation is repeated on dehydration —
     * that, rather than the rule, is what decides what reaches the column.
     */
    protected static function youTubeLink(): TextInput
    {
        return TextInput::make('youtube_video_id')
            ->label('YouTube link')
            ->columnSpanFull()
            ->placeholder('https://www.youtube.com/watch?v=...')
            ->helperText('Paste the link to the video. Only the video id is kept.')
            ->rule(new YouTubeVideoLink, fn (?string $state): bool => filled($state))
            ->dehydrateStateUsing(fn (?string $state): ?string => filled($state)
                ? YouTubeVideoId::fromInput($state)
                : null);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected static function fillRow(array $data): array
    {
        $ingredientId = $data['ingredient_id'] ?? null;

        $canonical = $ingredientId === null
            ? null
            : Ingredient::find($ingredientId)?->canonical_name;

        $data['name'] = $canonical ?? ($data['raw_text'] ?? '');

        return $data;
    }

    /**
     * One repeater row as recipe_ingredients columns.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected static function rowAttributes(array $data): array
    {
        /**
         * orderColumn() has already numbered the rows from one by the time
         * this runs. The API numbers from zero, and the two write the same
         * column, so the studio drops back a place to match rather than
         * leaving the catalogue with two conventions in one table.
         */
        $position = max(0, ((int) ($data['position'] ?? 1)) - 1);

        return app(RecipeIngredientWriter::class)->rowAttributes($data, $position);
    }

    /**
     * @param  array<string, mixed>  $state
     */
    protected static function ingredientLabel(array $state): ?string
    {
        $name = trim((string) ($state['name'] ?? ''));

        if ($name === '') {
            return null;
        }

        return trim(collect([
            $state['quantity'] ?? null,
            $state['unit'] ?? null,
            $name,
        ])->filter(fn (mixed $part): bool => filled($part))->implode(' '));
    }

    /**
     * @return array<int, string>
     */
    protected static function unitSymbols(): array
    {
        return Unit::query()->orderBy('symbol')->pluck('symbol')->all();
    }

    /**
     * @return array<int, string>
     */
    protected static function ingredientNames(): array
    {
        return Ingredient::query()
            ->orderBy('canonical_name')
            ->limit(500)
            ->pluck('canonical_name')
            ->all();
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
