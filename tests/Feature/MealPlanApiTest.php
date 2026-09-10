<?php

use App\Models\Ingredient;
use App\Models\MealPlan;
use App\Models\MealPlanItem;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\ShoppingListItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('authenticated user can view active meal plan', function () {
    $user = User::factory()->create();

    $res = $this->actingAs($user)->getJson('/api/meal-plan');

    $res->assertStatus(200)
        ->assertJsonPath('data.name', 'This week')
        ->assertJsonPath('data.is_active', true);
});

test('authenticated user can add recipe and adjust servings', function () {
    $user = User::factory()->create();
    $recipe = Recipe::factory()->create(['servings' => 4]);

    // Add recipe
    $addRes = $this->actingAs($user)->postJson('/api/meal-plan/items', [
        'recipe_id' => $recipe->id,
        'servings' => 4,
    ]);

    $addRes->assertStatus(200)
        ->assertJsonCount(1, 'data.items');

    $plan = MealPlan::where('user_id', $user->id)->first();
    $item = $plan->items()->first();

    // Update servings
    $updateRes = $this->actingAs($user)->patchJson("/api/meal-plan/items/{$item->id}", [
        'servings' => 8,
    ]);

    $updateRes->assertStatus(200)
        ->assertJsonPath('data.servings', 8);
});

test('authenticated user can remove recipe from meal plan', function () {
    $user = User::factory()->create();
    $recipe = Recipe::factory()->create();
    $plan = MealPlan::create(['user_id' => $user->id, 'is_active' => true]);
    $item = MealPlanItem::create(['meal_plan_id' => $plan->id, 'recipe_id' => $recipe->id, 'servings' => 4]);

    $res = $this->actingAs($user)->deleteJson("/api/meal-plan/items/{$item->id}");

    $res->assertStatus(200);
    $this->assertDatabaseMissing('meal_plan_items', ['id' => $item->id]);
});

test('generates merged shopping list with servings scaling and preserves checked state', function () {
    $user = User::factory()->create();
    $plan = MealPlan::create(['user_id' => $user->id, 'is_active' => true]);

    $onion = Ingredient::factory()->create(['canonical_name' => 'onion', 'default_dimension' => 'count']);

    // Recipe 1: 2 onions (servings = 4)
    $r1 = Recipe::factory()->create(['servings' => 4]);
    RecipeIngredient::create([
        'recipe_id' => $r1->id,
        'ingredient_id' => $onion->id,
        'quantity' => 2,
        'unit' => 'piece',
        'raw_text' => '2 onions',
        'position' => 0,
    ]);

    // Recipe 2: 1 onion (servings = 4)
    $r2 = Recipe::factory()->create(['servings' => 4]);
    RecipeIngredient::create([
        'recipe_id' => $r2->id,
        'ingredient_id' => $onion->id,
        'quantity' => 1,
        'unit' => 'piece',
        'raw_text' => '1 onion',
        'position' => 0,
    ]);

    // Add to plan: recipe 1 desired servings = 8 (2x), recipe 2 desired servings = 4 (1x)
    // 2 * 2 + 1 * 1 = 5 onions!
    MealPlanItem::create(['meal_plan_id' => $plan->id, 'recipe_id' => $r1->id, 'servings' => 8]);
    MealPlanItem::create(['meal_plan_id' => $plan->id, 'recipe_id' => $r2->id, 'servings' => 4]);

    // First generate
    $res1 = $this->actingAs($user)->postJson('/api/meal-plan/shopping-list');
    $res1->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.quantity', 5)
        ->assertJsonPath('data.0.unit', 'piece')
        ->assertJsonPath('data.0.is_checked', false);

    $itemId = $res1->json('data.0.id');

    // Toggle checked
    $toggleRes = $this->actingAs($user)->patchJson("/api/shopping-list/items/{$itemId}", [
        'is_checked' => true,
    ]);
    $toggleRes->assertStatus(200)
        ->assertJsonPath('data.is_checked', true);

    // Regenerate shopping list - checked state must be preserved (idempotent)!
    $res2 = $this->actingAs($user)->postJson('/api/meal-plan/shopping-list');
    $res2->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.quantity', 5)
        ->assertJsonPath('data.0.is_checked', true);
});

test('user cannot toggle shopping list item belonging to someone else', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();

    $plan = MealPlan::create(['user_id' => $owner->id, 'is_active' => true]);
    $item = ShoppingListItem::create([
        'meal_plan_id' => $plan->id,
        'display_name' => 'Garlic',
        'quantity' => 3,
        'unit' => 'piece',
        'is_checked' => false,
    ]);

    $res = $this->actingAs($other)->patchJson("/api/shopping-list/items/{$item->id}", [
        'is_checked' => true,
    ]);

    $res->assertStatus(404);
});
