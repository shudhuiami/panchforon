<?php

use App\DTOs\ParsedIngredient;
use App\DTOs\RecipePlanItemInput;
use App\Enums\UnitDimension;
use App\Services\MergeEngine;

beforeEach(function () {
    $this->engine = new MergeEngine;
});

// Case 1: Two recipes, same ingredient, same unit -> one merged line
test('case 1: two recipes with same ingredient and same unit merge into one line', function () {
    $items = [
        new RecipePlanItemInput(
            ingredient: new ParsedIngredient(
                quantity: 200.0,
                unit: 'g',
                name: 'chicken breast',
                rawText: '200g chicken breast',
                ingredientId: 1,
                dimension: UnitDimension::Mass
            ),
            servingsMultiplier: 1.0,
            recipeTitle: 'Recipe A'
        ),
        new RecipePlanItemInput(
            ingredient: new ParsedIngredient(
                quantity: 300.0,
                unit: 'g',
                name: 'chicken breast',
                rawText: '300g chicken breast',
                ingredientId: 1,
                dimension: UnitDimension::Mass
            ),
            servingsMultiplier: 1.0,
            recipeTitle: 'Recipe B'
        ),
    ];

    $merged = $this->engine->merge($items);

    expect($merged)->toHaveCount(1)
        ->and($merged[0]->displayName)->toBe('chicken breast')
        ->and($merged[0]->quantity)->toBe(500.0)
        ->and($merged[0]->unit)->toBe('g')
        ->and($merged[0]->sourceNote)->toBe('from 2 recipes');
});

// Case 2: Two recipes, same ingredient, different units same dimension -> one line, converted
test('case 2: two recipes with same ingredient and different units same dimension merge and convert', function () {
    $items = [
        new RecipePlanItemInput(
            ingredient: new ParsedIngredient(
                quantity: 500.0,
                unit: 'g',
                name: 'flour',
                rawText: '500g flour',
                ingredientId: 2,
                dimension: UnitDimension::Mass
            ),
            servingsMultiplier: 1.0
        ),
        new RecipePlanItemInput(
            ingredient: new ParsedIngredient(
                quantity: 1.0,
                unit: 'kg',
                name: 'flour',
                rawText: '1kg flour',
                ingredientId: 2,
                dimension: UnitDimension::Mass
            ),
            servingsMultiplier: 1.0
        ),
    ];

    $merged = $this->engine->merge($items);

    // 500g + 1000g = 1500g -> prettified to 1.5kg
    expect($merged)->toHaveCount(1)
        ->and($merged[0]->quantity)->toBe(1.5)
        ->and($merged[0]->unit)->toBe('kg');
});

// Case 3: Two recipes, same ingredient, different dimensions -> two lines
test('case 3: two recipes with same ingredient across different dimensions remain separate lines', function () {
    $items = [
        new RecipePlanItemInput(
            ingredient: new ParsedIngredient(
                quantity: 200.0,
                unit: 'g',
                name: 'chicken',
                rawText: '200g chicken',
                ingredientId: 3,
                dimension: UnitDimension::Mass
            ),
            servingsMultiplier: 1.0
        ),
        new RecipePlanItemInput(
            ingredient: new ParsedIngredient(
                quantity: 2.0,
                unit: 'piece',
                name: 'chicken',
                rawText: '2 pieces chicken',
                ingredientId: 3,
                dimension: UnitDimension::Count
            ),
            servingsMultiplier: 1.0
        ),
    ];

    $merged = $this->engine->merge($items);

    expect($merged)->toHaveCount(2);
    $units = array_map(fn ($line) => $line->unit, $merged);
    expect($units)->toContain('g')->and($units)->toContain('piece');
});

// Case 4: "Salt to taste" from three recipes -> three unmerged lines, no quantity invented
test('case 4: salt to taste from three recipes results in three unmerged lines without fabricated quantity', function () {
    $items = [
        new RecipePlanItemInput(
            ingredient: new ParsedIngredient(
                quantity: null,
                unit: null,
                name: 'salt',
                rawText: 'salt to taste',
                ingredientId: 4,
                dimension: UnitDimension::None
            ),
            recipeTitle: 'Curry'
        ),
        new RecipePlanItemInput(
            ingredient: new ParsedIngredient(
                quantity: null,
                unit: null,
                name: 'salt',
                rawText: 'salt to taste',
                ingredientId: 4,
                dimension: UnitDimension::None
            ),
            recipeTitle: 'Soup'
        ),
        new RecipePlanItemInput(
            ingredient: new ParsedIngredient(
                quantity: null,
                unit: null,
                name: 'salt',
                rawText: 'salt to taste',
                ingredientId: 4,
                dimension: UnitDimension::None
            ),
            recipeTitle: 'Roast'
        ),
    ];

    $merged = $this->engine->merge($items);

    expect($merged)->toHaveCount(3);
    foreach ($merged as $line) {
        expect($line->isUnmerged)->toBeTrue()
            ->and($line->quantity)->toBeNull()
            ->and($line->unit)->toBeNull();
    }
});

// Case 5: Servings multiplier of 2 doubles quantities
test('case 5: servings multiplier of 2 doubles quantities', function () {
    $items = [
        new RecipePlanItemInput(
            ingredient: new ParsedIngredient(
                quantity: 150.0,
                unit: 'g',
                name: 'basmati rice',
                rawText: '150g basmati rice',
                ingredientId: 5,
                dimension: UnitDimension::Mass
            ),
            servingsMultiplier: 2.0
        ),
    ];

    $merged = $this->engine->merge($items);

    expect($merged)->toHaveCount(1)
        ->and($merged[0]->quantity)->toBe(300.0)
        ->and($merged[0]->unit)->toBe('g');
});

// Case 6: Unparsed ingredient -> passthrough line with raw text
test('case 6: unparsed ingredient passes through with raw text', function () {
    $items = [
        new RecipePlanItemInput(
            ingredient: new ParsedIngredient(
                quantity: null,
                unit: null,
                name: null,
                rawText: 'secret grandmother seasoning blend',
                ingredientId: null,
                dimension: UnitDimension::None
            ),
            servingsMultiplier: 1.0,
            recipeTitle: 'Stew'
        ),
    ];

    $merged = $this->engine->merge($items);

    expect($merged)->toHaveCount(1)
        ->and($merged[0]->displayName)->toBe('secret grandmother seasoning blend')
        ->and($merged[0]->isUnmerged)->toBeTrue()
        ->and($merged[0]->quantity)->toBeNull();
});

// Case 7: Alias resolution: "coriander" + "cilantro" -> one line
test('case 7: alias resolution merges coriander and cilantro when mapped to same ingredient id', function () {
    $items = [
        new RecipePlanItemInput(
            ingredient: new ParsedIngredient(
                quantity: 1.0,
                unit: 'piece',
                name: 'coriander',
                rawText: '1 bunch fresh coriander',
                ingredientId: 10,
                dimension: UnitDimension::Count
            ),
            servingsMultiplier: 1.0
        ),
        new RecipePlanItemInput(
            ingredient: new ParsedIngredient(
                quantity: 2.0,
                unit: 'piece',
                name: 'coriander',
                rawText: '2 bunches cilantro',
                ingredientId: 10,
                dimension: UnitDimension::Count
            ),
            servingsMultiplier: 1.0
        ),
    ];

    $merged = $this->engine->merge($items);

    expect($merged)->toHaveCount(1)
        ->and($merged[0]->quantity)->toBe(3.0)
        ->and($merged[0]->sourceNote)->toBe('from 2 recipes');
});

// Case 8: Prettify: 1500g -> 1.5kg; 0.25 onion -> 1 onion
test('case 8: prettifies 1500g to 1.5kg and rounds up counts so 0.25 onion becomes 1 onion', function () {
    // Mass prettify
    $massItems = [
        new RecipePlanItemInput(
            ingredient: new ParsedIngredient(
                quantity: 750.0,
                unit: 'g',
                name: 'potato',
                rawText: '750g potato',
                ingredientId: 20,
                dimension: UnitDimension::Mass
            ),
            servingsMultiplier: 2.0
        ),
    ];
    $massMerged = $this->engine->merge($massItems);
    expect($massMerged[0]->quantity)->toBe(1.5)
        ->and($massMerged[0]->unit)->toBe('kg');

    // Count rounding up
    $countItems = [
        new RecipePlanItemInput(
            ingredient: new ParsedIngredient(
                quantity: 0.25,
                unit: 'piece',
                name: 'onion',
                rawText: '1/4 onion',
                ingredientId: 21,
                dimension: UnitDimension::Count
            ),
            servingsMultiplier: 1.0
        ),
    ];
    $countMerged = $this->engine->merge($countItems);
    expect($countMerged[0]->quantity)->toBe(1.0)
        ->and($countMerged[0]->unit)->toBe('piece');
});

// Case 9: Empty meal plan -> empty list, no error
test('case 9: empty meal plan returns empty list with no error', function () {
    $merged = $this->engine->merge([]);

    expect($merged)->toBeEmpty()
        ->and($merged)->toBeArray();
});

// Case 10: Single recipe -> no merging, quantities preserved exactly
test('case 10: single recipe preserves quantities exactly without merging notes', function () {
    $items = [
        new RecipePlanItemInput(
            ingredient: new ParsedIngredient(
                quantity: 250.0,
                unit: 'g',
                name: 'sugar',
                rawText: '250g sugar',
                ingredientId: 30,
                dimension: UnitDimension::Mass
            ),
            servingsMultiplier: 1.0,
            recipeTitle: 'Cake'
        ),
    ];

    $merged = $this->engine->merge($items);

    expect($merged)->toHaveCount(1)
        ->and($merged[0]->quantity)->toBe(250.0)
        ->and($merged[0]->unit)->toBe('g')
        ->and($merged[0]->sourceNote)->toBeNull();
});
