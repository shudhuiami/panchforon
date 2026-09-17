<?php

namespace App\Filament\Studio\Pages;

use App\Models\User;
use App\Services\YouTube\YouTubeChannel;
use App\Services\YouTube\YouTubeClient;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Image;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

/**
 * Where a creator points the site at their YouTube channel.
 *
 * Three columns on the user are all this writes — the channel id, its handle
 * and its title — and the id is the one that matters: it is what the video
 * picker lists uploads from while a recipe is being written.
 *
 * Nothing here proves ownership, and the page says so rather than letting a
 * connected badge imply it. The key this reads with is public and read-only,
 * so looking up a channel is something anyone may do to anyone; pasting a
 * handle is a claim. The check that counts is an admin reading the creator
 * application.
 *
 * @property-read Schema $form
 */
class Channel extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedVideoCamera;

    protected static ?string $navigationLabel = 'YouTube channel';

    protected static ?string $title = 'YouTube channel';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'channel';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    /**
     * Filament lets any authenticated panel user reach a custom page by
     * default, so the creator check is repeated here rather than resting on
     * the panel middleware alone — the habit SiteSettings has in the admin
     * panel, for the same reason.
     */
    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->isCreator() && ! $user->isSuspended();
    }

    public function mount(): void
    {
        $this->form->fill(['channel' => $this->connectedHandle()]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components($this->isAvailable() ? $this->connectionSchema() : self::unavailableSchema())
            ->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        $form = Form::make([EmbeddedSchema::make('form')])->id('form');

        /**
         * No key, no submit button. A form whose only outcome is "that could
         * not be looked up" is worse than no form at all.
         */
        if ($this->isAvailable()) {
            $form = $form
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([$this->connectAction()])->key('form-actions'),
                ]);
        }

        return $schema->components([$form]);
    }

    /**
     * Resolve what was pasted, and keep it only if YouTube knew it.
     *
     * A handle that does not resolve is the ordinary case — someone typed it
     * from memory — so it is reported plainly and nothing is written. There is
     * no partial save either: a channel is its id, handle and title together,
     * and the picker can do nothing with two of the three.
     */
    public function save(): void
    {
        $user = $this->creator();

        if (! $user instanceof User) {
            return;
        }

        $input = trim((string) ($this->form->getState()['channel'] ?? ''));

        if ($input === '') {
            Notification::make()
                ->warning()
                ->title('Nothing to look up')
                ->body('Paste your channel address, or your @handle on its own.')
                ->send();

            return;
        }

        $channel = app(YouTubeClient::class)->resolveChannel($input);

        if (! $channel instanceof YouTubeChannel) {
            Notification::make()
                ->danger()
                ->title('No channel found')
                ->body("YouTube has nothing at \"{$input}\". Check the handle — it is the @name at the top of your channel page — and try again.")
                ->send();

            return;
        }

        $user->update([
            'youtube_channel_id' => $channel->id,
            'youtube_channel_handle' => $channel->handle,
            'youtube_channel_title' => $channel->title,
        ]);

        $this->form->fill(['channel' => $this->connectedHandle()]);

        Notification::make()
            ->success()
            ->title('Channel connected')
            ->body("\"{$channel->title}\" is the channel your videos will be listed from.")
            ->send();
    }

    public function disconnect(): void
    {
        $user = $this->creator();

        if (! $user instanceof User) {
            return;
        }

        $user->update([
            'youtube_channel_id' => null,
            'youtube_channel_handle' => null,
            'youtube_channel_title' => null,
        ]);

        $this->form->fill(['channel' => null]);

        Notification::make()
            ->success()
            ->title('Channel disconnected')
            ->body('Recipes that already point at a video keep it. Only the picker is switched off.')
            ->send();
    }

    public function isConnected(): bool
    {
        return filled($this->creator()?->youtube_channel_id);
    }

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('disconnect')
                ->label('Disconnect')
                ->icon(Heroicon::OutlinedLinkSlash)
                ->color('danger')
                ->visible(fn (): bool => $this->isConnected())
                ->requiresConfirmation()
                ->modalHeading('Disconnect this channel?')
                ->modalDescription('The picker stops offering your uploads. Recipes that already carry a video keep it, and you can connect the channel again whenever you like.')
                ->modalSubmitActionLabel('Disconnect')
                ->action(fn () => $this->disconnect()),
        ];
    }

    protected function connectAction(): Action
    {
        return Action::make('connect')
            ->label($this->isConnected() ? 'Change channel' : 'Connect channel')
            ->submit('save')
            ->keyBindings(['mod+s']);
    }

    /**
     * @return array<int, mixed>
     */
    protected function connectionSchema(): array
    {
        return [
            Section::make('Your channel')
                ->description('Paste the address of your channel, or just the @handle. Only what it resolves to is stored: no password, and nothing is ever posted to YouTube on your behalf.')
                ->schema([
                    TextInput::make('channel')
                        ->label('Channel address or @handle')
                        ->placeholder('@panchforon')
                        ->maxLength(255)
                        ->autocomplete(false)
                        ->helperText('Either works: https://www.youtube.com/@panchforon, or @panchforon on its own.'),

                    /**
                     * Said out loud, on the page, rather than left for someone
                     * to infer from a tick they never saw.
                     */
                    Text::make('Connecting a channel does not prove it is yours — the lookup is public, so anyone could paste anyone. Your creator application is where that is actually checked.')
                        ->color('gray'),
                ]),

            Section::make('Connected')
                ->visible(fn (): bool => $this->isConnected())
                ->columns(['default' => 1, 'sm' => 4])
                ->schema([
                    Image::make(
                        url: fn (): string => (string) $this->connectedThumbnailUrl(),
                        alt: fn (): string => $this->connectedTitle() ?? 'Channel thumbnail',
                    )
                        ->imageHeight(88)
                        ->visible(fn (): bool => $this->connectedThumbnailUrl() !== null),

                    Text::make(fn (): string => $this->connectedSummary())
                        ->columnSpan(['sm' => 3]),
                ]),
        ];
    }

    /**
     * What the page is when there is no API key.
     *
     * The same posture an unconfigured social provider takes: the feature does
     * not exist rather than sitting there broken, so there is no box to type a
     * handle into that could never resolve one.
     *
     * @return array<int, mixed>
     */
    protected static function unavailableSchema(): array
    {
        return [
            Callout::make()
                ->warning()
                ->icon(Heroicon::OutlinedExclamationTriangle)
                ->heading('YouTube is not switched on for this site')
                ->description('No YouTube API key is configured, so channels cannot be looked up and uploads cannot be listed. You can still paste a video link onto a recipe by hand. Ask an administrator if you would like the picker turned on.'),
        ];
    }

    protected function isAvailable(): bool
    {
        return app(YouTubeClient::class)->isConfigured();
    }

    /**
     * Title, handle and id on one line, so a creator can tell at a glance that
     * the channel that resolved is the one they meant.
     */
    protected function connectedSummary(): string
    {
        $user = $this->creator();

        $parts = array_filter([
            $this->connectedTitle(),
            $this->connectedHandle(),
            $user?->youtube_channel_id,
        ], fn (mixed $part): bool => filled($part));

        return implode(' · ', array_map(fn (mixed $part): string => (string) $part, $parts));
    }

    protected function connectedTitle(): ?string
    {
        $title = $this->creator()?->youtube_channel_title;

        return filled($title) ? (string) $title : null;
    }

    /**
     * The stored handle with its @ put back on. YouTube reports a customUrl
     * without one and the client strips what it is given besides, but a handle
     * only reads as a handle with it.
     */
    protected function connectedHandle(): ?string
    {
        $handle = $this->creator()?->youtube_channel_handle;

        return filled($handle) ? '@'.ltrim((string) $handle, '@') : null;
    }

    /**
     * Looked up rather than stored: a channel picture changes, and a URL kept
     * on the user would be the one thing on this page able to go stale with
     * nobody touching it. The client's hour of cache makes asking free.
     *
     * Looked up by the id, never the handle — an owner can change a handle,
     * and the id is what the connection actually is.
     */
    protected function connectedThumbnailUrl(): ?string
    {
        $id = $this->creator()?->youtube_channel_id;

        if (blank($id)) {
            return null;
        }

        return app(YouTubeClient::class)->resolveChannel((string) $id)?->thumbnailUrl;
    }

    protected function creator(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }
}
