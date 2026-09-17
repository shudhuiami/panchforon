<?php

namespace App\Filament\Studio\Resources\Recipes\Schemas;

use App\Enums\SpiceLevel;
use App\Filament\Studio\Pages\Channel;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\Unit;
use App\Models\User;
use App\Rules\YouTubeVideoLink;
use App\Services\RecipeIngredientWriter;
use App\Services\YouTube\YouTubeChannel;
use App\Services\YouTube\YouTubeClient;
use App\Services\YouTube\YouTubeVideo;
use App\Support\YouTubeVideoId;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

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
 *   pasting the link to their own video is the case it exists for — with a
 *   picker beside it that lists the uploads of the channel they connected, so
 *   the usual case is two clicks rather than a trip to another tab.
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
            ->hintAction(self::videoPicker())
            ->rule(new YouTubeVideoLink, fn (?string $state): bool => filled($state))
            ->dehydrateStateUsing(fn (?string $state): ?string => filled($state)
                ? YouTubeVideoId::fromInput($state)
                : null);
    }

    /**
     * Pick the video instead of pasting it.
     *
     * The whole thing hangs off one string of state — the page tokens loaded
     * so far — and everything else is derived from it. That is deliberate:
     * a hidden input bound to an array is a value the browser turns into
     * "[object Object]" the moment anything touches it, and YouTube's page
     * tokens are short strings that walk the list perfectly well on their own.
     * Re-reading the earlier pages to rebuild the list costs nothing either,
     * because the client caches every page for an hour.
     *
     * Absent entirely when there is no API key: an unconfigured site behaves
     * as though the feature does not exist rather than offering a button that
     * opens onto nothing.
     */
    protected static function videoPicker(): Action
    {
        return Action::make('pickYouTubeVideo')
            ->label('Pick from my channel')
            ->icon(Heroicon::OutlinedVideoCamera)
            ->visible(fn (): bool => app(YouTubeClient::class)->isConfigured())
            ->modalHeading('Your uploads')
            ->modalWidth(Width::TwoExtraLarge)
            ->modalSubmitActionLabel('Use this video')
            /**
             * Nothing to submit when there was nothing to pick — no channel
             * connected, or a channel with no public uploads on it yet. A
             * submit button over an empty list only produces a validation
             * error about a choice that could not be made.
             */
            ->modalSubmitAction(fn (Action $action): Action|bool => self::hasSomethingToPick() ? $action : false)
            ->fillForm(fn (): array => ['page_tokens' => '', 'video_id' => null])
            ->schema(fn (): array => self::connectedChannel() instanceof YouTubeChannel
                ? self::uploadsPicker()
                : self::connectChannelPrompt())
            ->action(function (array $data, Set $set): void {
                /**
                 * Even our own list goes through the extractor on the way to
                 * the field, for the reason the client itself gives: only the
                 * bare eleven characters may ever reach an embed.
                 */
                $id = YouTubeVideoId::fromInput((string) ($data['video_id'] ?? ''));

                if ($id !== null) {
                    $set('youtube_video_id', $id);
                }
            });
    }

    /**
     * @return array<int, mixed>
     */
    protected static function uploadsPicker(): array
    {
        return [
            Hidden::make('page_tokens'),

            Radio::make('video_id')
                ->hiddenLabel()
                ->required()
                ->options(fn (Get $get): array => self::videoOptions(self::loadedVideos($get('page_tokens'))))
                ->descriptions(fn (Get $get): array => self::videoDescriptions(self::loadedVideos($get('page_tokens')))),

            Text::make('This channel has no public videos yet. Anything you upload will show up here.')
                ->color('gray')
                ->visible(fn (Get $get): bool => self::loadedVideos($get('page_tokens')) === []),

            Actions::make([
                Action::make('loadMore')
                    ->label('Show older videos')
                    ->icon(Heroicon::OutlinedChevronDown)
                    ->link()
                    ->visible(fn (Get $get): bool => self::nextPageToken($get('page_tokens')) !== null)
                    ->action(function (Get $get, Set $set): void {
                        $next = self::nextPageToken($get('page_tokens'));

                        if ($next === null) {
                            return;
                        }

                        $set('page_tokens', trim(((string) $get('page_tokens')).' '.$next));
                    }),
            ])->key('olderVideos'),
        ];
    }

    /**
     * What the modal is when the creator has not connected a channel.
     *
     * A grid with nothing in it reads as "you have no videos", which is a
     * different and more disheartening thing than "we do not know where your
     * videos are". So it says the second, and points at the page that fixes it.
     *
     * @return array<int, mixed>
     */
    protected static function connectChannelPrompt(): array
    {
        return [
            Callout::make()
                ->info()
                ->icon(Heroicon::OutlinedVideoCamera)
                ->heading('No channel connected yet')
                ->description('Connect your YouTube channel and your uploads will be listed here to choose from. Until then you can paste a video link by hand.')
                ->footerActions([
                    Action::make('connectChannel')
                        ->label('Connect a channel')
                        ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                        ->url(fn (): string => Channel::getUrl()),
                ]),
        ];
    }

    /**
     * A connected channel with at least one public upload on it. Both halves
     * are cached, so asking costs nothing after the first render.
     */
    protected static function hasSomethingToPick(): bool
    {
        return self::connectedChannel() instanceof YouTubeChannel
            && self::loadedVideos('') !== [];
    }

    /**
     * Every video across the pages loaded so far, in upload order.
     *
     * @return list<YouTubeVideo>
     */
    protected static function loadedVideos(mixed $pageTokens): array
    {
        return self::walk($pageTokens)['videos'];
    }

    /**
     * The token for the page after the last one loaded, or null at the end of
     * the channel.
     */
    protected static function nextPageToken(mixed $pageTokens): ?string
    {
        return self::walk($pageTokens)['next'];
    }

    /**
     * Read the first page, then each token that "show older" has added.
     *
     * @return array{videos: list<YouTubeVideo>, next: ?string}
     */
    protected static function walk(mixed $pageTokens): array
    {
        $channel = self::connectedChannel();

        if (! $channel instanceof YouTubeChannel) {
            return ['videos' => [], 'next' => null];
        }

        $client = app(YouTubeClient::class);

        /** The first page has no token; the rest are whatever has been added. */
        $tokens = [null, ...self::splitTokens($pageTokens)];

        $videos = [];
        $next = null;

        foreach ($tokens as $token) {
            $page = $client->uploads($channel->uploadsPlaylistId, $token);

            $videos = [...$videos, ...$page->videos];
            $next = $page->nextPageToken;
        }

        return ['videos' => $videos, 'next' => $next];
    }

    /**
     * @return list<string>
     */
    protected static function splitTokens(mixed $pageTokens): array
    {
        return array_values(array_filter(
            explode(' ', is_string($pageTokens) ? $pageTokens : ''),
            fn (string $token): bool => $token !== '',
        ));
    }

    /**
     * A thumbnail and a title per option.
     *
     * Radio escapes a plain string label and renders an Htmlable one as it
     * stands, so every value interpolated below is escaped by hand — the title
     * is whatever the channel owner typed, and the URL is whatever the API
     * returned. The src is additionally required to be https, so no other
     * scheme can reach an attribute the browser will fetch.
     *
     * @param  list<YouTubeVideo>  $videos
     * @return array<string, HtmlString>
     */
    protected static function videoOptions(array $videos): array
    {
        $options = [];

        foreach ($videos as $video) {
            $thumbnail = str_starts_with((string) $video->thumbnailUrl, 'https://')
                ? '<img src="'.e($video->thumbnailUrl).'" alt="" loading="lazy" '
                    .'style="width:8rem;height:4.5rem;flex:none;object-fit:cover;border-radius:0.375rem;" />'
                : '';

            $options[$video->id] = new HtmlString(
                '<span style="display:inline-flex;align-items:center;gap:0.75rem;">'
                .$thumbnail
                .'<span>'.e($video->title).'</span>'
                .'</span>'
            );
        }

        return $options;
    }

    /**
     * When each one went public, which is how a cook tells two versions of the
     * same dish apart. Radio escapes these, so they stay plain text.
     *
     * @param  list<YouTubeVideo>  $videos
     * @return array<string, string>
     */
    protected static function videoDescriptions(array $videos): array
    {
        $descriptions = [];

        foreach ($videos as $video) {
            $descriptions[$video->id] = $video->publishedAt?->format('j M Y') ?? 'Publication date unknown';
        }

        return $descriptions;
    }

    /**
     * The channel connected on the Channel page, looked up by its id.
     *
     * The id rather than the handle: a handle can be changed by its owner, the
     * id cannot, and the uploads playlist the picker actually needs only comes
     * back with the channel. One cached unit an hour.
     */
    protected static function connectedChannel(): ?YouTubeChannel
    {
        $user = Auth::user();

        if (! $user instanceof User || blank($user->youtube_channel_id)) {
            return null;
        }

        return app(YouTubeClient::class)->resolveChannel((string) $user->youtube_channel_id);
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
