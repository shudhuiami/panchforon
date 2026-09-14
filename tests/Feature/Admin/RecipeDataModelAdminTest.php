<?php

use App\Enums\SpiceLevel;
use App\Filament\Resources\Ingredients\Pages\EditIngredient;
use App\Filament\Resources\Ingredients\Pages\ListIngredients;
use App\Filament\Resources\Recipes\Pages\EditRecipe;
use App\Filament\Resources\Recipes\RecipeResource;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

test('an admin can take an ingredient off shopping lists', function () {
    $water = Ingredient::factory()->create(['canonical_name' => 'water']);

    expect($water->refresh()->is_shoppable)->toBeTrue();

    Livewire::actingAs($this->admin)
        ->test(EditIngredient::class, ['record' => $water->getKey()])
        ->fillForm(['is_shoppable' => false])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($water->refresh()->is_shoppable)->toBeFalse();
});

test('the Bengali name and the preferred unit save with the ingredient', function () {
    $ingredient = Ingredient::factory()->create(['canonical_name' => 'coriander']);

    Livewire::actingAs($this->admin)
        ->test(EditIngredient::class, ['record' => $ingredient->getKey()])
        ->fillForm([
            'name_bn' => 'ধনে',
            'preferred_unit' => 'g',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $ingredient->refresh();

    expect($ingredient->name_bn)->toBe('ধনে')
        ->and($ingredient->preferred_unit)->toBe('g');
});

test('the shopping list filter separates what is bought from what is not', function () {
    $onion = Ingredient::factory()->create(['canonical_name' => 'onion', 'is_shoppable' => true]);
    $water = Ingredient::factory()->create(['canonical_name' => 'water', 'is_shoppable' => false]);

    Livewire::actingAs($this->admin)
        ->test(ListIngredients::class)
        ->filterTable('is_shoppable', false)
        ->assertCanSeeTableRecords([$water])
        ->assertCanNotSeeTableRecords([$onion]);
});

test('the cooking metadata saves from the recipe form', function () {
    $recipe = Recipe::factory()->create([
        'prep_minutes' => null,
        'cook_minutes' => null,
        'spice_level' => null,
    ]);

    Livewire::actingAs($this->admin)
        ->test(EditRecipe::class, ['record' => $recipe->getKey()])
        ->fillForm([
            'name_bn' => 'মুরগির ঝোল',
            'prep_minutes' => 20,
            'cook_minutes' => 45,
            'spice_level' => SpiceLevel::Medium->value,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $recipe->refresh();

    expect($recipe->name_bn)->toBe('মুরগির ঝোল')
        ->and($recipe->prep_minutes)->toBe(20)
        ->and($recipe->cook_minutes)->toBe(45)
        ->and($recipe->spice_level)->toBe(SpiceLevel::Medium);
});

test('the recipe screen shows prep and cook added up', function () {
    $recipe = Recipe::factory()->create(['prep_minutes' => 20, 'cook_minutes' => 45]);

    $this->actingAs($this->admin)
        ->get(RecipeResource::getUrl('view', ['record' => $recipe]))
        ->assertSuccessful()
        ->assertSee('65 min');
});

test('a recipe with no times given has no total, rather than a total of zero', function () {
    $recipe = Recipe::factory()->make(['prep_minutes' => null, 'cook_minutes' => null]);

    expect(RecipeResource::totalMinutes($recipe))->toBeNull();
});

test('a recipe with only one of the two times still totals', function () {
    $recipe = Recipe::factory()->make(['prep_minutes' => null, 'cook_minutes' => 45]);

    expect(RecipeResource::totalMinutes($recipe))->toBe(45);
});
