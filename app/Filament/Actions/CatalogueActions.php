<?php

namespace App\Filament\Actions;

use App\Services\MealDbImporter;
use App\Services\SettingsRepository;
use App\Services\TaxonomyRenamer;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Throwable;

/**
 * Catalogue-wide jobs, kept on the recipes screen because that is where the
 * catalogue is. A separate navigation item for each would be one more thing
 * to find and two more places to look.
 */
class CatalogueActions
{
    /**
     * Pull recipes from TheMealDB.
     *
     * The import runs inside the request: the deployment target has no queue
     * worker, so a background job would never start. The limit is capped well
     * below the console command's so a click cannot outrun the PHP time limit
     * on shared hosting; the command is still there for larger runs.
     */
    public static function importFromMealDb(): Action
    {
        return Action::make('importRecipes')
            ->label('Import recipes')
            ->icon(Heroicon::OutlinedArrowDownTray)
            ->color('gray')
            ->authorize('moderateAny')
            ->modalHeading('Import from TheMealDB')
            ->modalDescription('Runs now, while you wait. Recipes already imported are skipped, so running it twice is safe.')
            ->modalSubmitActionLabel('Start the import')
            ->schema([
                TextInput::make('limit')
                    ->label('How many at most')
                    ->numeric()
                    ->required()
                    ->default(25)
                    ->minValue(1)
                    ->maxValue(100)
                    ->helperText('Keep it modest: the import runs in this request. Use the console command for a large catalogue.'),

                Select::make('areas')
                    ->label('Cuisines')
                    ->multiple()
                    ->required()
                    ->searchable()
                    /**
                     * Every cuisine TheMealDB publishes, not only the ones
                     * already saved in settings: limiting the list to the
                     * setting would make this modal unusable before anyone
                     * had filled that setting in.
                     */
                    ->options(fn (): array => collect(self::mealDbAreas())
                        ->mapWithKeys(fn (string $area): array => [$area => $area])
                        ->all())
                    ->default(fn (): array => app(SettingsRepository::class)->array('mealdb_import_areas'))
                    ->helperText('Starts from the list on the settings screen. Add or remove any for this run.'),
            ])
            ->action(function (array $data): void {
                /** @var array<int, string> $areas */
                $areas = array_values((array) $data['areas']);

                try {
                    $imported = app(MealDbImporter::class)->import((int) $data['limit'], $areas);
                } catch (Throwable $e) {
                    Notification::make()
                        ->danger()
                        ->title('The import did not finish')
                        ->body($e->getMessage())
                        ->persistent()
                        ->send();

                    return;
                }

                Notification::make()
                    ->success()
                    ->title($imported.' '.str('recipe')->plural($imported).' imported')
                    ->body($imported === 0 ? 'Nothing new: every recipe returned was already in the catalogue.' : 'They are live in the catalogue now.')
                    ->send();
            });
    }

    /**
     * The cuisines TheMealDB publishes.
     *
     * Hard-coded on purpose: the list changes rarely, and fetching it would
     * mean a network round trip before the modal could even open, on a host
     * that may not reach the internet at all.
     *
     * @return list<string>
     */
    public static function mealDbAreas(): array
    {
        return [
            'American', 'British', 'Canadian', 'Chinese', 'Croatian', 'Dutch', 'Egyptian', 'Filipino',
            'French', 'Greek', 'Indian', 'Irish', 'Italian', 'Jamaican', 'Japanese', 'Kenyan',
            'Malaysian', 'Mexican', 'Moroccan', 'Polish', 'Portuguese', 'Russian', 'Spanish',
            'Thai', 'Tunisian', 'Turkish', 'Ukrainian', 'Uruguayan', 'Vietnamese',
        ];
    }

    /**
     * Rename a cuisine or category everywhere it is used.
     */
    public static function renameTaxonomy(string $field): Action
    {
        $label = ucfirst($field);

        return Action::make("rename{$label}")
            ->label("Rename a {$field}")
            ->icon(Heroicon::OutlinedTag)
            ->color('gray')
            ->authorize('moderateAny')
            ->modalHeading("Rename a {$field}")
            ->modalDescription('Changes it on every recipe that uses it. Renaming onto a name already in use merges the two.')
            ->modalSubmitActionLabel('Rename')
            ->schema([
                Select::make('from')
                    ->label("The {$field} to change")
                    ->required()
                    ->searchable()
                    ->options(fn (): array => collect(app(TaxonomyRenamer::class)->values($field))
                        ->mapWithKeys(fn (int $count, string $value): array => [$value => "{$value} ({$count})"])
                        ->all()),

                TextInput::make('to')
                    ->label('New name')
                    ->required()
                    ->maxLength(255),
            ])
            ->action(function (array $data) use ($field): void {
                $changed = app(TaxonomyRenamer::class)->rename($field, (string) $data['from'], (string) $data['to']);

                Notification::make()
                    ->success()
                    ->title('Renamed')
                    ->body($changed.' '.str('recipe')->plural($changed).' updated.')
                    ->send();
            });
    }
}
