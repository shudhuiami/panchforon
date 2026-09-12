<?php

use App\Filament\Resources\Ingredients\IngredientResource;
use App\Filament\Resources\Ingredients\Pages\ListIngredients;
use App\Filament\Resources\Recipes\Pages\ListRecipes;
use App\Models\Ingredient;
use App\Models\IngredientAlias;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\User;
use App\Services\TaxonomyRenamer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
});

test('an ordinary cook cannot reach the ingredient dictionary', function () {
    $this->actingAs(User::factory()->create())
        ->get(IngredientResource::getUrl('index'))
        ->assertForbidden();
});

test('the unused tab shows only ingredients no recipe uses', function () {
    $used = Ingredient::factory()->create(['canonical_name' => 'onion']);
    RecipeIngredient::factory()->create(['ingredient_id' => $used->id]);
    $orphan = Ingredient::factory()->create(['canonical_name' => 'corriander']);

    Livewire::actingAs($this->admin)
        ->test(ListIngredients::class, ['activeTab' => 'unused'])
        ->assertCanSeeTableRecords([$orphan])
        ->assertCanNotSeeTableRecords([$used]);
});

test('merging from the dictionary moves the recipe lines and keeps the old name as an alias', function () {
    $keep = Ingredient::factory()->create(['canonical_name' => 'coriander']);
    $duplicate = Ingredient::factory()->create(['canonical_name' => 'corriander']);
    RecipeIngredient::factory()->count(2)->create(['ingredient_id' => $duplicate->id]);

    Livewire::actingAs($this->admin)
        ->test(ListIngredients::class)
        ->callTableAction('merge', $duplicate, ['into' => $keep->id]);

    expect(Ingredient::find($duplicate->id))->toBeNull()
        ->and(RecipeIngredient::where('ingredient_id', $keep->id)->count())->toBe(2)
        ->and(IngredientAlias::where('alias', 'corriander')->value('ingredient_id'))->toBe($keep->id);
});

test('renaming a cuisine changes every recipe that uses it', function () {
    Recipe::factory()->count(3)->create(['cuisine' => 'bangladeshi']);
    Recipe::factory()->create(['cuisine' => 'Italian']);

    Livewire::actingAs($this->admin)
        ->test(ListRecipes::class)
        ->callAction('renameCuisine', ['from' => 'bangladeshi', 'to' => 'Bangladeshi']);

    expect(Recipe::where('cuisine', 'Bangladeshi')->count())->toBe(3)
        ->and(Recipe::where('cuisine', 'bangladeshi')->count())->toBe(0);
});

test('renaming onto a name already in use merges the two', function () {
    Recipe::factory()->count(2)->create(['cuisine' => 'bangladeshi']);
    Recipe::factory()->count(3)->create(['cuisine' => 'Bangladeshi']);

    app(TaxonomyRenamer::class)->rename('cuisine', 'bangladeshi', 'Bangladeshi');

    expect(Recipe::where('cuisine', 'Bangladeshi')->count())->toBe(5)
        ->and(array_keys(app(TaxonomyRenamer::class)->values('cuisine')))->toBe(['Bangladeshi']);
});

test('only cuisine and category can be renamed', function () {
    expect(fn () => app(TaxonomyRenamer::class)->rename('title', 'a', 'b'))->toThrow(RuntimeException::class)
        ->and(fn () => app(TaxonomyRenamer::class)->values('instructions'))->toThrow(RuntimeException::class);
});

test('a blank new name is refused', function () {
    Recipe::factory()->create(['cuisine' => 'Thai']);

    expect(fn () => app(TaxonomyRenamer::class)->rename('cuisine', 'Thai', '   '))->toThrow(RuntimeException::class);
});

test('the value list counts how many recipes carry each name', function () {
    Recipe::factory()->count(2)->create(['category' => 'Curry']);
    Recipe::factory()->create(['category' => 'Rice']);
    Recipe::factory()->create(['category' => null]);

    expect(app(TaxonomyRenamer::class)->values('category'))->toBe(['Curry' => 2, 'Rice' => 1]);
});

test('an admin can import recipes without leaving the panel', function () {
    Http::fake([
        'themealdb.com/api/json/v1/1/filter.php*' => Http::response(['meals' => [['idMeal' => '52772']]]),
        'themealdb.com/api/json/v1/1/lookup.php*' => Http::response(['meals' => [[
            'idMeal' => '52772',
            'strMeal' => 'Teriyaki Chicken Casserole',
            'strArea' => 'Japanese',
            'strCategory' => 'Chicken',
            'strInstructions' => 'Preheat the oven.',
            'strMealThumb' => 'https://img.test/teriyaki.jpg',
            'strIngredient1' => 'soy sauce',
            'strMeasure1' => '3/4 cup',
        ]]]),
    ]);

    Livewire::actingAs($this->admin)
        ->test(ListRecipes::class)
        ->callAction('importRecipes', ['limit' => 5, 'areas' => ['Japanese']]);

    expect(Recipe::where('external_id', '52772')->exists())->toBeTrue();
});

test('an import that fails reports the problem instead of throwing', function () {
    Http::fake(['themealdb.com/*' => fn () => throw new RuntimeException('TheMealDB is unreachable.')]);

    Livewire::actingAs($this->admin)
        ->test(ListRecipes::class)
        ->callAction('importRecipes', ['limit' => 5, 'areas' => ['Japanese']])
        ->assertHasNoActionErrors();

    expect(Recipe::count())->toBe(0);
});
