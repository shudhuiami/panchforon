<?php

use App\Models\Ingredient;
use App\Models\MealPlan;
use App\Models\MealPlanItem;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * A recipe on the cook's current plan, with the rows given.
 *
 * @param  array<int, array<string, mixed>>  $rows
 */
function shoppingListRecipe(MealPlan $plan, array $rows, int $servings = 4): Recipe
{
    $recipe = Recipe::factory()->create(['servings' => 4]);

    foreach ($rows as $position => $row) {
        RecipeIngredient::create([
            'recipe_id' => $recipe->id,
            'position' => $position,
        ] + $row);
    }

    MealPlanItem::create([
        'meal_plan_id' => $plan->id,
        'recipe_id' => $recipe->id,
        'servings' => $servings,
    ]);

    return $recipe;
}

function shoppingListPlanFor(User $user): MealPlan
{
    return MealPlan::create(['user_id' => $user->id, 'is_active' => true]);
}

test('the same potato measured two ways stays two lines on the list', function () {
    $user = User::factory()->create();
    $plan = shoppingListPlanFor($user);

    $potato = Ingredient::factory()->create([
        'canonical_name' => 'potato',
        'default_dimension' => 'mass',
    ]);

    shoppingListRecipe($plan, [[
        'ingredient_id' => $potato->id,
        'quantity' => 400,
        'unit' => 'g',
        'raw_text' => '400g potatoes, cubed',
    ]]);

    shoppingListRecipe($plan, [[
        'ingredient_id' => $potato->id,
        'quantity' => 3,
        'unit' => 'piece',
        'raw_text' => '3 potatoes',
    ]]);

    $res = $this->actingAs($user)->postJson('/api/meal-plan/shopping-list');

    $res->assertStatus(200)->assertJsonCount(2, 'data');

    $lines = collect($res->json('data'))->map(fn (array $line): string => $line['quantity'].' '.$line['unit'])->all();

    expect($lines)->toEqualCanonicalizing(['400 g', '3 piece'])
        ->and(collect($res->json('data'))->pluck('display_name')->unique()->all())->toBe(['potato']);
});

test('the same onion weighed in two recipes becomes one line', function () {
    $user = User::factory()->create();
    $plan = shoppingListPlanFor($user);

    $onion = Ingredient::factory()->create([
        'canonical_name' => 'onion',
        'default_dimension' => 'count',
    ]);

    foreach ([200, 300] as $grams) {
        shoppingListRecipe($plan, [[
            'ingredient_id' => $onion->id,
            'quantity' => $grams,
            'unit' => 'g',
            'raw_text' => "{$grams}g onion",
        ]]);
    }

    $this->actingAs($user)->postJson('/api/meal-plan/shopping-list')
        ->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.display_name', 'onion')
        ->assertJsonPath('data.0.quantity', 500)
        ->assertJsonPath('data.0.unit', 'g');
});

test('two tablespoons of ginger paste are a volume, not two grams', function () {
    $user = User::factory()->create();
    $plan = shoppingListPlanFor($user);

    // The ingredient record says ginger is usually weighed; this recipe spoons it.
    $ginger = Ingredient::factory()->create([
        'canonical_name' => 'ginger',
        'default_dimension' => 'mass',
    ]);

    shoppingListRecipe($plan, [[
        'ingredient_id' => $ginger->id,
        'quantity' => 2,
        'unit' => 'tbsp',
        'raw_text' => '2 tbsp ginger paste',
    ]]);

    $this->actingAs($user)->postJson('/api/meal-plan/shopping-list')
        ->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.display_name', 'ginger')
        ->assertJsonPath('data.0.quantity', 29.6)
        ->assertJsonPath('data.0.unit', 'ml');
});

test('nobody shops for water', function () {
    $user = User::factory()->create();
    $plan = shoppingListPlanFor($user);

    $water = Ingredient::factory()->create([
        'canonical_name' => 'water',
        'default_dimension' => 'volume',
        'is_shoppable' => false,
    ]);

    $rice = Ingredient::factory()->create([
        'canonical_name' => 'rice',
        'default_dimension' => 'mass',
    ]);

    shoppingListRecipe($plan, [
        ['ingredient_id' => $water->id, 'quantity' => 500, 'unit' => 'ml', 'raw_text' => '500ml water'],
        ['ingredient_id' => $rice->id, 'quantity' => 300, 'unit' => 'g', 'raw_text' => '300g rice'],
    ]);

    $res = $this->actingAs($user)->postJson('/api/meal-plan/shopping-list');

    $res->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.display_name', 'rice');

    $this->assertDatabaseMissing('shopping_list_items', ['ingredient_id' => $water->id]);
});

test('a line is optional only when every recipe called it optional', function () {
    $user = User::factory()->create();
    $plan = shoppingListPlanFor($user);

    $yogurt = Ingredient::factory()->create(['canonical_name' => 'yogurt', 'default_dimension' => 'mass']);
    $cashew = Ingredient::factory()->create(['canonical_name' => 'cashew', 'default_dimension' => 'mass']);

    shoppingListRecipe($plan, [
        ['ingredient_id' => $yogurt->id, 'quantity' => 100, 'unit' => 'g', 'is_optional' => true, 'raw_text' => '100g yogurt'],
        ['ingredient_id' => $cashew->id, 'quantity' => 50, 'unit' => 'g', 'is_optional' => true, 'raw_text' => '50g cashew'],
    ]);

    shoppingListRecipe($plan, [
        ['ingredient_id' => $yogurt->id, 'quantity' => 200, 'unit' => 'g', 'is_optional' => false, 'raw_text' => '200g yogurt'],
    ]);

    $this->actingAs($user)->postJson('/api/meal-plan/shopping-list')->assertStatus(200);

    $this->assertDatabaseHas('shopping_list_items', [
        'ingredient_id' => $cashew->id,
        'is_optional' => true,
    ]);

    $this->assertDatabaseHas('shopping_list_items', [
        'ingredient_id' => $yogurt->id,
        'quantity' => 300,
        'is_optional' => false,
    ]);
});

test('an imported row with nothing but its sentence is still read', function () {
    $user = User::factory()->create();
    $plan = shoppingListPlanFor($user);

    $garlic = Ingredient::factory()->create(['canonical_name' => 'garlic', 'default_dimension' => 'count']);

    shoppingListRecipe($plan, [[
        'ingredient_id' => $garlic->id,
        'quantity' => null,
        'unit' => null,
        'raw_text' => '4 cloves garlic, crushed',
    ]]);

    $this->actingAs($user)->postJson('/api/meal-plan/shopping-list')
        ->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.display_name', 'garlic')
        ->assertJsonPath('data.0.quantity', 4)
        ->assertJsonPath('data.0.unit', 'clove');
});

test('the list says what the ingredient is, not how one recipe cut it', function () {
    $user = User::factory()->create();
    $plan = shoppingListPlanFor($user);

    $beef = Ingredient::factory()->create(['canonical_name' => 'beef', 'default_dimension' => 'mass']);

    shoppingListRecipe($plan, [[
        'ingredient_id' => $beef->id,
        'quantity' => 700,
        'unit' => 'g',
        'raw_text' => '700g beef chuck, cubed',
    ]]);

    $this->actingAs($user)->postJson('/api/meal-plan/shopping-list')
        ->assertStatus(200)
        ->assertJsonPath('data.0.display_name', 'beef');
});

test('doubling a recipe doubles the spoons it needs', function () {
    $user = User::factory()->create();
    $plan = shoppingListPlanFor($user);

    $mustardOil = Ingredient::factory()->create(['canonical_name' => 'mustard oil', 'default_dimension' => 'volume']);

    shoppingListRecipe($plan, [[
        'ingredient_id' => $mustardOil->id,
        'quantity' => 3,
        'unit' => 'tbsp',
        'raw_text' => '3 tbsp mustard oil',
    ]], servings: 8);

    // 3 tbsp * 2 = 6 * 14.7868 = 88.7208 ml
    $this->actingAs($user)->postJson('/api/meal-plan/shopping-list')
        ->assertStatus(200)
        ->assertJsonPath('data.0.quantity', 88.7)
        ->assertJsonPath('data.0.unit', 'ml');
});
