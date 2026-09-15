<?php

use App\Enums\MealSlot;
use App\Models\Ingredient;
use App\Models\MealPlan;
use App\Models\MealPlanItem;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a dish can be added to a chosen day and slot', function () {
    $user = User::factory()->create();
    $recipe = Recipe::factory()->create();
    $today = CarbonImmutable::today();
    MealPlan::factory()->for($user)->startingOn($today, 7)->create();

    $res = $this->actingAs($user)->postJson('/api/meal-plan/items', [
        'recipe_id' => $recipe->id,
        'servings' => 2,
        'planned_for' => $today->addDays(3)->toDateString(),
        'meal_slot' => 'breakfast',
    ]);

    $res->assertStatus(200)
        ->assertJsonCount(1, 'data.items')
        ->assertJsonPath('data.items.0.planned_for', $today->addDays(3)->toDateString())
        ->assertJsonPath('data.items.0.meal_slot', 'breakfast')
        ->assertJsonPath('data.items.0.servings', 2)
        ->assertJsonPath('data.items_count', 1);
});

test('a dish added without a day lands on the plan first day and at dinner', function () {
    $user = User::factory()->create();
    $recipe = Recipe::factory()->create();
    $startsOn = CarbonImmutable::today()->addDays(4);
    MealPlan::factory()->for($user)->startingOn($startsOn, 7)->create();

    $res = $this->actingAs($user)->postJson('/api/meal-plan/items', [
        'recipe_id' => $recipe->id,
    ]);

    $res->assertStatus(200)
        ->assertJsonPath('data.items.0.planned_for', $startsOn->toDateString())
        ->assertJsonPath('data.items.0.meal_slot', 'dinner')
        ->assertJsonPath('data.items.0.servings', 4);
});

test('a dish added to a plan already under way lands on today', function () {
    $user = User::factory()->create();
    $recipe = Recipe::factory()->create();
    $today = CarbonImmutable::today();
    MealPlan::factory()->for($user)->startingOn($today->subDays(2), 7)->create();

    $this->actingAs($user)->postJson('/api/meal-plan/items', [
        'recipe_id' => $recipe->id,
    ])->assertStatus(200)
        ->assertJsonPath('data.items.0.planned_for', $today->toDateString());
});

test('a day outside the plan is refused', function () {
    $user = User::factory()->create();
    $recipe = Recipe::factory()->create();
    $today = CarbonImmutable::today();
    MealPlan::factory()->for($user)->startingOn($today, 7)->create();

    $this->actingAs($user)->postJson('/api/meal-plan/items', [
        'recipe_id' => $recipe->id,
        'planned_for' => $today->addDays(9)->toDateString(),
    ])->assertStatus(422)->assertJsonValidationErrors('planned_for');

    $this->actingAs($user)->postJson('/api/meal-plan/items', [
        'recipe_id' => $recipe->id,
        'planned_for' => $today->subDay()->toDateString(),
    ])->assertStatus(422)->assertJsonValidationErrors('planned_for');
});

test('an unknown slot is refused', function () {
    $user = User::factory()->create();
    $recipe = Recipe::factory()->create();
    MealPlan::factory()->for($user)->create();

    $this->actingAs($user)->postJson('/api/meal-plan/items', [
        'recipe_id' => $recipe->id,
        'meal_slot' => 'elevenses',
    ])->assertStatus(422)->assertJsonValidationErrors('meal_slot');
});

test('the same dish can sit on two days and in two slots of one day', function () {
    $user = User::factory()->create();
    $recipe = Recipe::factory()->create();
    $today = CarbonImmutable::today();
    MealPlan::factory()->for($user)->startingOn($today, 7)->create();

    $this->actingAs($user)->postJson('/api/meal-plan/items', [
        'recipe_id' => $recipe->id,
        'planned_for' => $today->toDateString(),
        'meal_slot' => 'lunch',
    ])->assertStatus(200);

    $this->actingAs($user)->postJson('/api/meal-plan/items', [
        'recipe_id' => $recipe->id,
        'planned_for' => $today->toDateString(),
        'meal_slot' => 'dinner',
    ])->assertStatus(200);

    $res = $this->actingAs($user)->postJson('/api/meal-plan/items', [
        'recipe_id' => $recipe->id,
        'planned_for' => $today->addDay()->toDateString(),
        'meal_slot' => 'lunch',
    ]);

    $res->assertStatus(200)->assertJsonPath('data.items_count', 3);
});

test('re-adding the same dish to the same day and slot just changes the servings', function () {
    $user = User::factory()->create();
    $recipe = Recipe::factory()->create();
    $today = CarbonImmutable::today();
    MealPlan::factory()->for($user)->startingOn($today, 7)->create();

    $this->actingAs($user)->postJson('/api/meal-plan/items', [
        'recipe_id' => $recipe->id,
        'planned_for' => $today->toDateString(),
        'servings' => 4,
    ])->assertStatus(200);

    $this->actingAs($user)->postJson('/api/meal-plan/items', [
        'recipe_id' => $recipe->id,
        'planned_for' => $today->toDateString(),
        'servings' => 10,
    ])->assertStatus(200)
        ->assertJsonPath('data.items_count', 1)
        ->assertJsonPath('data.items.0.servings', 10);
});

test('a dish can be dragged to another day, slot and serving count', function () {
    $user = User::factory()->create();
    $recipe = Recipe::factory()->create();
    $today = CarbonImmutable::today();
    $plan = MealPlan::factory()->for($user)->startingOn($today, 7)->create();
    $item = MealPlanItem::factory()->for($plan)->create([
        'recipe_id' => $recipe->id,
        'planned_for' => $today,
        'meal_slot' => MealSlot::Dinner,
    ]);

    $this->actingAs($user)->patchJson("/api/meal-plan/items/{$item->id}", [
        'planned_for' => $today->addDays(2)->toDateString(),
        'meal_slot' => 'lunch',
        'servings' => 6,
    ])->assertStatus(200)
        ->assertJsonPath('data.planned_for', $today->addDays(2)->toDateString())
        ->assertJsonPath('data.meal_slot', 'lunch')
        ->assertJsonPath('data.servings', 6);
});

test('a dish can be set loose from the calendar and put back', function () {
    $user = User::factory()->create();
    $recipe = Recipe::factory()->create();
    $today = CarbonImmutable::today();
    $plan = MealPlan::factory()->for($user)->startingOn($today, 7)->create();
    $item = MealPlanItem::factory()->for($plan)->create([
        'recipe_id' => $recipe->id,
        'planned_for' => $today,
    ]);

    $this->actingAs($user)->patchJson("/api/meal-plan/items/{$item->id}", [
        'planned_for' => null,
    ])->assertStatus(200)->assertJsonPath('data.planned_for', null);

    $this->actingAs($user)->patchJson("/api/meal-plan/items/{$item->id}", [
        'planned_for' => $today->addDay()->toDateString(),
    ])->assertStatus(200)->assertJsonPath('data.planned_for', $today->addDay()->toDateString());
});

test('a dish cannot be moved outside the plan', function () {
    $user = User::factory()->create();
    $recipe = Recipe::factory()->create();
    $today = CarbonImmutable::today();
    $plan = MealPlan::factory()->for($user)->startingOn($today, 7)->create();
    $item = MealPlanItem::factory()->for($plan)->create([
        'recipe_id' => $recipe->id,
        'planned_for' => $today,
    ]);

    $this->actingAs($user)->patchJson("/api/meal-plan/items/{$item->id}", [
        'planned_for' => $today->addDays(30)->toDateString(),
    ])->assertStatus(422)->assertJsonValidationErrors('planned_for');

    expect($item->refresh()->planned_for->toDateString())->toBe($today->toDateString());
});

test('a dish cannot be dropped onto a day and slot it already occupies', function () {
    $user = User::factory()->create();
    $recipe = Recipe::factory()->create();
    $today = CarbonImmutable::today();
    $plan = MealPlan::factory()->for($user)->startingOn($today, 7)->create();

    MealPlanItem::factory()->for($plan)->create([
        'recipe_id' => $recipe->id,
        'planned_for' => $today,
        'meal_slot' => MealSlot::Lunch,
    ]);
    $moving = MealPlanItem::factory()->for($plan)->create([
        'recipe_id' => $recipe->id,
        'planned_for' => $today,
        'meal_slot' => MealSlot::Dinner,
    ]);

    $this->actingAs($user)->patchJson("/api/meal-plan/items/{$moving->id}", [
        'meal_slot' => 'lunch',
    ])->assertStatus(422)->assertJsonValidationErrors('planned_for');
});

test('an empty change to a dish is refused', function () {
    $user = User::factory()->create();
    $recipe = Recipe::factory()->create();
    $plan = MealPlan::factory()->for($user)->create();
    $item = MealPlanItem::factory()->for($plan)->create(['recipe_id' => $recipe->id]);

    $this->actingAs($user)->patchJson("/api/meal-plan/items/{$item->id}", [])
        ->assertStatus(422);
});

test("a cook cannot touch someone else's dish", function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $recipe = Recipe::factory()->create();
    $plan = MealPlan::factory()->for($owner)->create();
    $item = MealPlanItem::factory()->for($plan)->create(['recipe_id' => $recipe->id]);

    $this->actingAs($stranger)->patchJson("/api/meal-plan/items/{$item->id}", ['servings' => 2])
        ->assertStatus(404);
    $this->actingAs($stranger)->deleteJson("/api/meal-plan/items/{$item->id}")
        ->assertStatus(404);
});

test('the plan hands back its dishes in day order with the undated ones first', function () {
    $user = User::factory()->create();
    $today = CarbonImmutable::today();
    $plan = MealPlan::factory()->for($user)->startingOn($today, 7)->create();

    $third = MealPlanItem::factory()->for($plan)->create([
        'recipe_id' => Recipe::factory()->create()->id,
        'planned_for' => $today->addDays(4),
    ]);
    $second = MealPlanItem::factory()->for($plan)->create([
        'recipe_id' => Recipe::factory()->create()->id,
        'planned_for' => $today->addDay(),
    ]);
    $first = MealPlanItem::factory()->for($plan)->unscheduled()->create([
        'recipe_id' => Recipe::factory()->create()->id,
    ]);

    $this->actingAs($user)->getJson('/api/meal-plan')
        ->assertStatus(200)
        ->assertJsonPath('data.items.0.id', $first->id)
        ->assertJsonPath('data.items.1.id', $second->id)
        ->assertJsonPath('data.items.2.id', $third->id)
        ->assertJsonPath('data.items.0.planned_for', null);
});

test('the shopping list still merges and keeps ticks across a plan with dates', function () {
    $user = User::factory()->create();
    $today = CarbonImmutable::today();
    $plan = MealPlan::factory()->for($user)->startingOn($today, 7)->create();

    $onion = Ingredient::factory()->create(['canonical_name' => 'onion', 'default_dimension' => 'count']);

    $recipe = Recipe::factory()->create(['servings' => 4]);
    RecipeIngredient::create([
        'recipe_id' => $recipe->id,
        'ingredient_id' => $onion->id,
        'quantity' => 2,
        'unit' => 'piece',
        'raw_text' => '2 onions',
        'position' => 0,
    ]);

    // The same dish cooked twice in the week has to add up to one line.
    MealPlanItem::factory()->for($plan)->create([
        'recipe_id' => $recipe->id,
        'servings' => 4,
        'planned_for' => $today,
        'meal_slot' => MealSlot::Lunch,
    ]);
    MealPlanItem::factory()->for($plan)->create([
        'recipe_id' => $recipe->id,
        'servings' => 4,
        'planned_for' => $today->addDays(3),
        'meal_slot' => MealSlot::Dinner,
    ]);

    $first = $this->actingAs($user)->postJson('/api/meal-plan/shopping-list');
    $first->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.quantity', 4);

    $itemId = $first->json('data.0.id');

    $this->actingAs($user)->patchJson("/api/shopping-list/items/{$itemId}", ['is_checked' => true])
        ->assertStatus(200);

    $this->actingAs($user)->postJson('/api/meal-plan/shopping-list')
        ->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.quantity', 4)
        ->assertJsonPath('data.0.is_checked', true);
});
