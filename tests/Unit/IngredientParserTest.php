<?php

use App\Enums\UnitDimension;
use App\Services\IngredientParser;

beforeEach(function () {
    $this->parser = new IngredientParser;
});

test('extracts integer quantities and count dimension', function () {
    $parsed = $this->parser->parse('2 onions');

    expect($parsed->quantity)->toBe(2.0)
        ->and($parsed->unit)->toBe('piece')
        ->and($parsed->name)->toBe('onion')
        ->and($parsed->dimension)->toBe(UnitDimension::Count);
});

test('extracts decimal quantities and mass dimension', function () {
    $parsed = $this->parser->parse('1.5 kg chicken breast');

    expect($parsed->quantity)->toBe(1.5)
        ->and($parsed->unit)->toBe('kg')
        ->and($parsed->name)->toBe('chicken breast')
        ->and($parsed->dimension)->toBe(UnitDimension::Mass);
});

test('extracts vulgar fractions', function () {
    $parsed = $this->parser->parse('½ tsp salt');

    expect($parsed->quantity)->toBe(0.5)
        ->and($parsed->unit)->toBe('tsp')
        ->and($parsed->name)->toBe('salt')
        ->and($parsed->dimension)->toBe(UnitDimension::Volume);
});

test('extracts mixed numbers and strips descriptors', function () {
    $parsed = $this->parser->parse('1 1/2 cups plain flour, sifted');

    expect($parsed->quantity)->toBe(1.5)
        ->and($parsed->unit)->toBe('cup')
        ->and($parsed->name)->toBe('plain flour')
        ->and($parsed->dimension)->toBe(UnitDimension::Volume);
});

test('extracts ranges using midpoint', function () {
    $parsed = $this->parser->parse('2-3 green chilies');

    expect($parsed->quantity)->toBe(2.5)
        ->and($parsed->unit)->toBe('piece')
        ->and($parsed->name)->toBe('green chili')
        ->and($parsed->dimension)->toBe(UnitDimension::Count);
});

test('handles descriptors like chopped and minced', function () {
    $parsed = $this->parser->parse('4 garlic cloves, finely minced');

    expect($parsed->quantity)->toBe(4.0)
        ->and($parsed->unit)->toBe('clove')
        ->and($parsed->name)->toBe('garlic')
        ->and($parsed->dimension)->toBe(UnitDimension::Count);
});

test('normalizes and singularizes plurals', function () {
    $parsed = $this->parser->parse('3 large tomatoes');

    expect($parsed->quantity)->toBe(3.0)
        ->and($parsed->name)->toBe('tomato');
});

test('handles to taste as unmerged with null quantity and unit', function () {
    $parsed = $this->parser->parse('salt to taste');

    expect($parsed->quantity)->toBeNull()
        ->and($parsed->unit)->toBeNull()
        ->and($parsed->name)->toBe('salt')
        ->and($parsed->dimension)->toBe(UnitDimension::None);
});

test('uses identity resolver callback when provided', function () {
    $resolver = function (string $name) {
        return $name === 'onion' ? 42 : null;
    };

    $parsed = $this->parser->parse('2 onions', $resolver);

    expect($parsed->ingredientId)->toBe(42);
});

test('preserves raw text on unparseable string', function () {
    $parsed = $this->parser->parse('');

    expect($parsed->quantity)->toBeNull()
        ->and($parsed->unit)->toBeNull()
        ->and($parsed->name)->toBeNull()
        ->and($parsed->rawText)->toBe('');
});
