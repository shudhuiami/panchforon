<?php

use App\Enums\UnitDimension;
use App\Models\Unit;
use App\Support\UnitRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

test('the registry the application hands round is the units table', function () {
    $registry = app(UnitRegistry::class);

    expect($registry->all())->toHaveCount(Unit::query()->count())
        ->and($registry->dimensionOf('tbsp'))->toBe(UnitDimension::Volume)
        ->and($registry->dimensionOf('g'))->toBe(UnitDimension::Mass)
        ->and($registry->dimensionOf('piece'))->toBe(UnitDimension::Count)
        ->and($registry->factorFor('kg'))->toBe(1000.0)
        ->and($registry->dimensionOf('bunch'))->toBeNull()
        ->and($registry->factorFor('bunch'))->toBe(1.0);
});

test('everything that needs the units gets the same copy of them', function () {
    expect(app(UnitRegistry::class))->toBe(app(UnitRegistry::class));
});

test('editing a unit forgets the cached table', function () {
    Unit::cachedDefinitions();

    expect(Cache::has(Unit::CACHE_KEY))->toBeTrue();

    Unit::query()->findOrFail('tbsp')->update(['factor_to_canonical' => 15.0]);

    expect(Cache::has(Unit::CACHE_KEY))->toBeFalse();

    $tbsp = collect(Unit::cachedDefinitions())->firstWhere('symbol', 'tbsp');

    expect($tbsp?->factorToCanonical)->toBe(15.0);
});

test('deleting a unit forgets the cached table', function () {
    Unit::cachedDefinitions();

    Unit::query()->findOrFail('floz')->delete();

    expect(Cache::has(Unit::CACHE_KEY))->toBeFalse()
        ->and(UnitRegistry::fromDefinitions(Unit::cachedDefinitions())->find('floz'))->toBeNull();
});

test('the cached units survive a store that serialises what it holds', function () {
    /**
     * The array store hands back the very objects it was given, so it can
     * never catch this. A store that serialises — the database one this app
     * runs on — hands back whatever the class definition allows, and an
     * object cached under a class that later moves comes back incomplete.
     */
    config(['cache.default' => 'database']);
    Cache::store('database')->clear();

    /** Populate the cache, then throw away the resolved singleton. */
    app(UnitRegistry::class);
    app()->forgetInstance(UnitRegistry::class);

    $cached = Cache::store('database')->get(Unit::CACHE_KEY);

    expect($cached)->toBeArray()->not->toBeEmpty();

    foreach ($cached as $row) {
        expect($row)->toBeArray('units are cached as plain values, never as serialised objects');
    }

    /** Read back through the cache: the registry still works. */
    $registry = app(UnitRegistry::class);

    expect($registry->dimensionOf('tbsp'))->toBe(UnitDimension::Volume)
        ->and($registry->factorFor('tbsp'))->toBe(14.7868)
        ->and($registry->find('kg')->name)->toBe('kilogram');
});
