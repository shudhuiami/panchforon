<?php

use App\Models\Setting;
use App\Services\SettingsRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('falls back to configured defaults when nothing is stored', function () {
    $settings = app(SettingsRepository::class);

    expect($settings->string('site_name'))->toBe('Panchforon')
        ->and($settings->boolean('registration_open'))->toBeTrue()
        ->and($settings->boolean('submissions_open'))->toBeTrue();
});

test('stored values take precedence over defaults', function () {
    Setting::query()->create(['key' => 'site_name', 'value' => 'Panchforon Kitchen']);

    expect(app(SettingsRepository::class)->string('site_name'))->toBe('Panchforon Kitchen');
});

test('reads the settings table once and serves later reads from cache', function () {
    Setting::query()->create(['key' => 'site_name', 'value' => 'Cached Name']);

    $settings = app(SettingsRepository::class);
    $settings->flush();

    DB::enableQueryLog();

    $settings->get('site_name');
    $settings->get('contact_email');
    $settings->get('registration_open');
    $settings->get('submissions_open');

    $settingsQueries = collect(DB::getQueryLog())
        ->filter(fn (array $query): bool => str_contains($query['query'], 'settings'));

    DB::disableQueryLog();

    expect($settingsQueries)->toHaveCount(1);
});

test('writing a setting invalidates the cached copy', function () {
    $settings = app(SettingsRepository::class);

    expect($settings->string('site_name'))->toBe('Panchforon');

    $settings->set('site_name', 'Renamed');

    expect($settings->string('site_name'))->toBe('Renamed')
        ->and(Setting::query()->whereKey('site_name')->value('value'))->toBe('Renamed');
});

test('setMany persists every value and flushes once', function () {
    app(SettingsRepository::class)->setMany([
        'site_name' => 'Bulk Name',
        'registration_open' => false,
    ]);

    $settings = app(SettingsRepository::class);

    expect($settings->string('site_name'))->toBe('Bulk Name')
        ->and($settings->boolean('registration_open'))->toBeFalse()
        ->and(Setting::query()->count())->toBe(2);
});

test('typed accessors coerce stored json values', function () {
    app(SettingsRepository::class)->setMany([
        'mealdb_import_limit' => 42,
        'mealdb_import_areas' => ['Thai', 'Japanese'],
    ]);

    $settings = app(SettingsRepository::class);

    expect($settings->integer('mealdb_import_limit'))->toBe(42)
        ->and($settings->array('mealdb_import_areas'))->toBe(['Thai', 'Japanese']);
});
