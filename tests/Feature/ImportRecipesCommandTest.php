<?php

use App\Services\IngredientParser;
use App\Services\MealDbImporter;
use App\Services\RankingService;
use App\Services\SettingsRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Records what the command asked the importer to do, without making a request.
 */
function fakeImporter(): MealDbImporter
{
    return new class(app(IngredientParser::class), app(RankingService::class)) extends MealDbImporter
    {
        public ?int $limit = null;

        /** @var array<int, string>|null */
        public ?array $areas = null;

        public function import(int $limit = 300, array $areas = []): int
        {
            $this->limit = $limit;
            $this->areas = $areas;

            return 0;
        }
    };
}

test('the import command uses the configured settings by default', function () {
    app(SettingsRepository::class)->setMany([
        'mealdb_import_limit' => 42,
        'mealdb_import_areas' => ['Thai', 'Japanese'],
    ]);

    $importer = fakeImporter();
    $this->app->instance(MealDbImporter::class, $importer);

    $this->artisan('recipes:import')->assertSuccessful();

    expect($importer->limit)->toBe(42)
        ->and($importer->areas)->toBe(['Thai', 'Japanese']);
});

test('command options override the configured settings', function () {
    app(SettingsRepository::class)->setMany([
        'mealdb_import_limit' => 42,
        'mealdb_import_areas' => ['Thai'],
    ]);

    $importer = fakeImporter();
    $this->app->instance(MealDbImporter::class, $importer);

    $this->artisan('recipes:import --limit=5 --area=Indian --area=Italian')->assertSuccessful();

    expect($importer->limit)->toBe(5)
        ->and($importer->areas)->toBe(['Indian', 'Italian']);
});

test('the import command falls back to the packaged defaults', function () {
    $importer = fakeImporter();
    $this->app->instance(MealDbImporter::class, $importer);

    $this->artisan('recipes:import')->assertSuccessful();

    expect($importer->limit)->toBe(config('settings.defaults.mealdb_import_limit'))
        ->and($importer->areas)->toBe(config('settings.defaults.mealdb_import_areas'));
});

test('the import command refuses an empty cuisine list', function () {
    app(SettingsRepository::class)->set('mealdb_import_areas', []);

    $importer = fakeImporter();
    $this->app->instance(MealDbImporter::class, $importer);

    $this->artisan('recipes:import')->assertFailed();

    expect($importer->areas)->toBeNull();
});

test('the import command rejects an unknown source', function () {
    $this->artisan('recipes:import --source=allrecipes')->assertFailed();
});
