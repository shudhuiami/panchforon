<?php

namespace App\Filament\Pages;

use App\Services\SettingsRepository;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * Single screen for everything site-wide.
 *
 * Values are written through SettingsRepository, which flushes its cache in the
 * same request. No secrets are stored here: credentials stay in .env, and the
 * integrations tab only reports whether each one is configured.
 *
 * @property-read Schema $form
 */
class SiteSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'Settings';

    protected static ?string $title = 'Site settings';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'site-settings';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $settings = app(SettingsRepository::class);

        $this->form->fill([
            'site_name' => $settings->string('site_name'),
            'contact_email' => $settings->string('contact_email'),
            'registration_open' => $settings->boolean('registration_open'),
            'submissions_open' => $settings->boolean('submissions_open'),
            'mealdb_import_limit' => $settings->integer('mealdb_import_limit'),
            'mealdb_import_areas' => $settings->array('mealdb_import_areas'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make()
                    ->persistTabInQueryString()
                    ->tabs([
                        Tab::make('Site')
                            ->icon(Heroicon::OutlinedGlobeAlt)
                            ->schema([
                                TextInput::make('site_name')
                                    ->label('Site name')
                                    ->required()
                                    ->maxLength(100)
                                    ->helperText('Shown in page titles and outgoing email.'),

                                TextInput::make('contact_email')
                                    ->label('Contact email')
                                    ->email()
                                    ->required()
                                    ->maxLength(255)
                                    ->helperText('Where people are told to write when they need help.'),
                            ]),

                        Tab::make('Features')
                            ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
                            ->schema([
                                Toggle::make('registration_open')
                                    ->label('Registration open')
                                    ->helperText('When off, POST /api/register is refused and nobody new can sign up.'),

                                Toggle::make('submissions_open')
                                    ->label('Recipe submissions open')
                                    ->helperText('When off, members cannot submit new recipes. Existing recipes are unaffected.'),
                            ]),

                        Tab::make('Integrations')
                            ->icon(Heroicon::OutlinedPuzzlePiece)
                            ->schema([
                                Section::make('TheMealDB')
                                    ->description('Import defaults for `php artisan recipes:import`. The free tier uses the public test key in the URL path, so there is no secret to store.')
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('mealdb_import_limit')
                                            ->label('Import limit')
                                            ->numeric()
                                            ->minValue(1)
                                            ->maxValue(2000)
                                            ->required()
                                            ->helperText('Maximum recipes to pull in one run.'),

                                        TagsInput::make('mealdb_import_areas')
                                            ->label('Cuisines to import')
                                            ->placeholder('Add a cuisine')
                                            ->helperText('TheMealDB area names, for example Indian or Italian.')
                                            ->columnSpanFull(),
                                    ]),

                                Section::make('Mail')
                                    ->description('Read-only. Credentials come from the environment, never from the database, so rotating APP_KEY cannot strand them and they stay out of database backups.')
                                    ->columns(3)
                                    ->schema(self::environmentSummary()),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([$this->saveAction()])->key('form-actions'),
                ]),
        ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        app(SettingsRepository::class)->setMany([
            'site_name' => $data['site_name'],
            'contact_email' => $data['contact_email'],
            'registration_open' => (bool) $data['registration_open'],
            'submissions_open' => (bool) $data['submissions_open'],
            'mealdb_import_limit' => (int) $data['mealdb_import_limit'],
            'mealdb_import_areas' => array_values($data['mealdb_import_areas'] ?? []),
        ]);

        Notification::make()
            ->success()
            ->title('Settings saved')
            ->body('The cached copy was refreshed, so the change is live now.')
            ->send();
    }

    protected function saveAction(): Action
    {
        return Action::make('save')
            ->label('Save settings')
            ->submit('save')
            ->keyBindings(['mod+s']);
    }

    /**
     * Reports which mail settings the environment supplies.
     *
     * Secrets are reduced to "configured" or "not configured" before they ever
     * reach a component, so no credential is rendered, logged, or held in the
     * Livewire payload.
     *
     * @return array<int, Text>
     */
    private static function environmentSummary(): array
    {
        /** @var array<string, array{value: mixed, secret: bool}> $checks */
        $checks = [
            'Mailer' => ['value' => config('mail.default'), 'secret' => false],
            'Host' => ['value' => config('mail.mailers.smtp.host'), 'secret' => false],
            'Port' => ['value' => config('mail.mailers.smtp.port'), 'secret' => false],
            'From address' => ['value' => config('mail.from.address'), 'secret' => false],
            'Username' => ['value' => config('mail.mailers.smtp.username'), 'secret' => true],
            'Password' => ['value' => config('mail.mailers.smtp.password'), 'secret' => true],
        ];

        $components = [];

        foreach ($checks as $label => $check) {
            $isSet = filled($check['value']);

            $display = match (true) {
                ! $isSet => 'not configured',
                $check['secret'] => str_repeat('•', 12).' configured',
                default => (string) $check['value'],
            };

            $components[] = Text::make("{$label}: {$display}")
                ->color($isSet ? 'gray' : 'warning');
        }

        return $components;
    }
}
