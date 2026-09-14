<?php

use App\DTOs\ParsedIngredient;
use App\DTOs\RecipePlanItemInput;
use App\DTOs\ShoppingListLine;
use App\Enums\UnitDimension;
use App\Services\MergeEngine;
use App\Support\UnitDefinition;
use App\Support\UnitRegistry;

/**
 * The units as the table holds them, written out here so the engine can be
 * exercised without a database.
 */
function mergeEngineUnits(): UnitRegistry
{
    $rows = [
        ['g', 'gram', UnitDimension::Mass, 1.0],
        ['kg', 'kilogram', UnitDimension::Mass, 1000.0],
        ['oz', 'ounce', UnitDimension::Mass, 28.3495],
        ['lb', 'pound', UnitDimension::Mass, 453.592],
        ['ml', 'millilitre', UnitDimension::Volume, 1.0],
        ['l', 'litre', UnitDimension::Volume, 1000.0],
        ['tsp', 'teaspoon', UnitDimension::Volume, 4.92892],
        ['tbsp', 'tablespoon', UnitDimension::Volume, 14.7868],
        ['cup', 'cup', UnitDimension::Volume, 236.588],
        ['floz', 'fluid ounce', UnitDimension::Volume, 29.5735],
        ['piece', 'piece', UnitDimension::Count, 1.0],
        ['clove', 'clove', UnitDimension::Count, 1.0],
        ['slice', 'slice', UnitDimension::Count, 1.0],
        ['whole', 'whole', UnitDimension::Count, 1.0],
    ];

    return UnitRegistry::fromDefinitions(array_map(
        fn (array $row): UnitDefinition => new UnitDefinition($row[0], $row[1], $row[2], $row[3]),
        $rows,
    ));
}

/**
 * A recipe row as the controller hands it over: the unit is the row's own, and
 * $ingredientDimension is the ingredient record's default, which is consulted
 * only when the unit cannot answer.
 */
function mergeEngineRow(
    int $ingredientId,
    string $name,
    ?float $quantity,
    ?string $unit,
    ?UnitDimension $ingredientDimension = null,
    bool $isOptional = false,
    float $multiplier = 1.0,
    ?string $recipeTitle = null,
): RecipePlanItemInput {
    return new RecipePlanItemInput(
        ingredient: new ParsedIngredient(
            quantity: $quantity,
            unit: $unit,
            name: $name,
            rawText: trim(($quantity ?? '').' '.($unit ?? '').' '.$name),
            ingredientId: $ingredientId,
            dimension: $ingredientDimension,
            isOptional: $isOptional,
        ),
        servingsMultiplier: $multiplier,
        recipeTitle: $recipeTitle,
    );
}

/**
 * @param  array<int, ShoppingListLine>  $lines
 * @return array<string, ShoppingListLine>
 */
function mergeEngineLinesByUnit(array $lines): array
{
    $keyed = [];

    foreach ($lines as $line) {
        $keyed[$line->unit ?? ''] = $line;
    }

    return $keyed;
}

beforeEach(function () {
    $this->engine = new MergeEngine(mergeEngineUnits());
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

// The rule the whole model turns on: the row's unit says what is being measured.

test('potato by weight and potato by the piece stay two lines and never add up', function () {
    $merged = $this->engine->merge([
        mergeEngineRow(ingredientId: 40, name: 'potato', quantity: 400.0, unit: 'g', ingredientDimension: UnitDimension::Mass, recipeTitle: 'Recipe A'),
        mergeEngineRow(ingredientId: 40, name: 'potato', quantity: 3.0, unit: 'piece', ingredientDimension: UnitDimension::Mass, recipeTitle: 'Recipe B'),
    ]);

    $byUnit = mergeEngineLinesByUnit($merged);

    expect($merged)->toHaveCount(2)
        ->and(array_keys($byUnit))->toEqualCanonicalizing(['g', 'piece'])
        ->and($byUnit['g']->quantity)->toBe(400.0)
        ->and($byUnit['piece']->quantity)->toBe(3.0)
        ->and(array_map(fn ($line) => $line->quantity, $merged))->not->toContain(403.0);
});

test('two recipes weighing the same onion add up to one line', function () {
    $merged = $this->engine->merge([
        mergeEngineRow(ingredientId: 41, name: 'onion', quantity: 200.0, unit: 'g', ingredientDimension: UnitDimension::Count, recipeTitle: 'Recipe A'),
        mergeEngineRow(ingredientId: 41, name: 'onion', quantity: 300.0, unit: 'g', ingredientDimension: UnitDimension::Count, recipeTitle: 'Recipe B'),
    ]);

    expect($merged)->toHaveCount(1)
        ->and($merged[0]->displayName)->toBe('onion')
        ->and($merged[0]->quantity)->toBe(500.0)
        ->and($merged[0]->unit)->toBe('g');
});

test('a spoonful of a thing usually weighed is still a spoonful, not grams', function () {
    // 2 tbsp of ginger paste, of an ingredient whose default dimension is mass.
    $merged = $this->engine->merge([
        mergeEngineRow(ingredientId: 42, name: 'ginger', quantity: 2.0, unit: 'tbsp', ingredientDimension: UnitDimension::Mass, recipeTitle: 'Chicken Roast'),
    ]);

    expect($merged)->toHaveCount(1)
        ->and($merged[0]->unit)->toBe('ml')
        ->and($merged[0]->quantity)->toBe(29.6);
});

test('spoons of the same thing across two recipes add up in volume', function () {
    $merged = $this->engine->merge([
        mergeEngineRow(ingredientId: 43, name: 'ginger', quantity: 2.0, unit: 'tbsp', ingredientDimension: UnitDimension::Mass),
        mergeEngineRow(ingredientId: 43, name: 'ginger', quantity: 3.0, unit: 'tsp', ingredientDimension: UnitDimension::Mass),
    ]);

    // 2 * 14.7868 + 3 * 4.92892 = 44.3605...
    expect($merged)->toHaveCount(1)
        ->and($merged[0]->unit)->toBe('ml')
        ->and($merged[0]->quantity)->toBe(44.4)
        ->and($merged[0]->sourceNote)->toBe('from 2 recipes');
});

test('a line is optional only when every recipe behind it said so', function () {
    $allOptional = $this->engine->merge([
        mergeEngineRow(ingredientId: 44, name: 'yogurt', quantity: 100.0, unit: 'g', isOptional: true),
        mergeEngineRow(ingredientId: 44, name: 'yogurt', quantity: 50.0, unit: 'g', isOptional: true),
    ]);

    $mixed = $this->engine->merge([
        mergeEngineRow(ingredientId: 45, name: 'yogurt', quantity: 100.0, unit: 'g', isOptional: true),
        mergeEngineRow(ingredientId: 45, name: 'yogurt', quantity: 50.0, unit: 'g', isOptional: false),
    ]);

    expect($allOptional)->toHaveCount(1)
        ->and($allOptional[0]->isOptional)->toBeTrue()
        ->and($mixed)->toHaveCount(1)
        ->and($mixed[0]->isOptional)->toBeFalse()
        ->and($mixed[0]->quantity)->toBe(150.0);
});

test('an optional line that cannot be merged keeps its flag', function () {
    $merged = $this->engine->merge([
        mergeEngineRow(ingredientId: 46, name: 'coriander', quantity: null, unit: null, isOptional: true, recipeTitle: 'Curry'),
    ]);

    expect($merged)->toHaveCount(1)
        ->and($merged[0]->isUnmerged)->toBeTrue()
        ->and($merged[0]->isOptional)->toBeTrue();
});

test('a unit nobody has taught us falls back to what the ingredient usually is', function () {
    $merged = $this->engine->merge([
        mergeEngineRow(ingredientId: 47, name: 'coriander', quantity: 2.0, unit: 'bunch', ingredientDimension: UnitDimension::Count),
    ]);

    expect($merged)->toHaveCount(1)
        ->and($merged[0]->isUnmerged)->toBeFalse()
        ->and($merged[0]->quantity)->toBe(2.0)
        ->and($merged[0]->unit)->toBe('bunch');
});

test('a line with no unit falls back to the ingredient dimension', function () {
    $merged = $this->engine->merge([
        mergeEngineRow(ingredientId: 48, name: 'chicken', quantity: 800.0, unit: null, ingredientDimension: UnitDimension::Mass),
    ]);

    expect($merged)->toHaveCount(1)
        ->and($merged[0]->quantity)->toBe(800.0)
        ->and($merged[0]->unit)->toBe('g');
});

test('an unknown unit with nothing to fall back on is left unmerged rather than guessed at', function () {
    $merged = $this->engine->merge([
        mergeEngineRow(ingredientId: 49, name: 'saffron', quantity: 1.0, unit: 'pinch', ingredientDimension: null, recipeTitle: 'Polao'),
    ]);

    expect($merged)->toHaveCount(1)
        ->and($merged[0]->isUnmerged)->toBeTrue()
        ->and($merged[0]->quantity)->toBeNull()
        ->and($merged[0]->sourceNote)->toBe('from Polao');
});
