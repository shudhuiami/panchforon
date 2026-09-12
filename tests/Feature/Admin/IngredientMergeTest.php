<?php

use App\Models\Ingredient;
use App\Models\IngredientAlias;
use App\Models\RecipeIngredient;
use App\Services\IngredientMerger;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('merging moves every recipe line onto the surviving ingredient', function () {
    $keep = Ingredient::factory()->create(['canonical_name' => 'coriander']);
    $duplicate = Ingredient::factory()->create(['canonical_name' => 'corriander']);
    RecipeIngredient::factory()->count(3)->create(['ingredient_id' => $duplicate->id]);
    RecipeIngredient::factory()->create(['ingredient_id' => $keep->id]);

    $moved = app(IngredientMerger::class)->merge($duplicate, $keep);

    expect($moved)->toBe(3)
        ->and(RecipeIngredient::where('ingredient_id', $keep->id)->count())->toBe(4)
        ->and(Ingredient::find($duplicate->id))->toBeNull();
});

test('the retired name becomes an alias, so the next import resolves it', function () {
    $keep = Ingredient::factory()->create(['canonical_name' => 'coriander']);
    $duplicate = Ingredient::factory()->create(['canonical_name' => 'Corriander']);

    app(IngredientMerger::class)->merge($duplicate, $keep);

    expect(IngredientAlias::where('ingredient_id', $keep->id)->pluck('alias')->all())->toContain('corriander');
});

test('aliases of the retired ingredient move across too', function () {
    $keep = Ingredient::factory()->create(['canonical_name' => 'coriander']);
    $duplicate = Ingredient::factory()->create(['canonical_name' => 'corriander']);
    IngredientAlias::factory()->create(['ingredient_id' => $duplicate->id, 'alias' => 'fresh coriander']);

    app(IngredientMerger::class)->merge($duplicate, $keep);

    expect(IngredientAlias::where('ingredient_id', $keep->id)->pluck('alias')->all())
        ->toContain('fresh coriander')
        ->and(IngredientAlias::count())->toBe(2);
});

test('an ingredient cannot be merged into itself', function () {
    $ingredient = Ingredient::factory()->create();

    expect(fn () => app(IngredientMerger::class)->merge($ingredient, $ingredient))
        ->toThrow(RuntimeException::class);
});

test('an alias that would duplicate the surviving name is not added', function () {
    $keep = Ingredient::factory()->create(['canonical_name' => 'coriander']);
    $duplicate = Ingredient::factory()->create(['canonical_name' => 'CORIANDER ']);

    app(IngredientMerger::class)->merge($duplicate, $keep);

    expect(IngredientAlias::count())->toBe(0);
});
