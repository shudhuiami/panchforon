<?php

use App\Models\MealPlan;
use App\Models\MealPlanItem;
use App\Models\Recipe;
use App\Models\ShoppingListItem;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a cook can start a plan over a chosen range', function () {
    $user = User::factory()->create();
    $startsOn = CarbonImmutable::today()->addDay();

    $res = $this->actingAs($user)->postJson('/api/meal-plans', [
        'name' => 'Eid week',
        'starts_on' => $startsOn->toDateString(),
        'ends_on' => $startsOn->addDays(6)->toDateString(),
    ]);

    $res->assertStatus(201)
        ->assertJsonPath('data.name', 'Eid week')
        ->assertJsonPath('data.is_active', true)
        ->assertJsonPath('data.starts_on', $startsOn->toDateString())
        ->assertJsonPath('data.ends_on', $startsOn->addDays(6)->toDateString())
        ->assertJsonPath('data.day_count', 7)
        ->assertJsonPath('data.items_count', 0)
        ->assertJsonPath('data.shopping_items_count', 0);
});

test('a plan cannot start in the past', function () {
    $user = User::factory()->create();
    $yesterday = CarbonImmutable::today()->subDay();

    $res = $this->actingAs($user)->postJson('/api/meal-plans', [
        'starts_on' => $yesterday->toDateString(),
        'ends_on' => $yesterday->addDays(6)->toDateString(),
    ]);

    $res->assertStatus(422)->assertJsonValidationErrors('starts_on');
});

test('a plan cannot end before it starts', function () {
    $user = User::factory()->create();
    $today = CarbonImmutable::today();

    $res = $this->actingAs($user)->postJson('/api/meal-plans', [
        'starts_on' => $today->addDays(3)->toDateString(),
        'ends_on' => $today->toDateString(),
    ]);

    $res->assertStatus(422)->assertJsonValidationErrors('ends_on');
});

test('a plan cannot run longer than sixty days', function () {
    $user = User::factory()->create();
    $today = CarbonImmutable::today();

    $this->actingAs($user)->postJson('/api/meal-plans', [
        'starts_on' => $today->toDateString(),
        'ends_on' => $today->addDays(59)->toDateString(),
    ])->assertStatus(201);

    $this->actingAs($user)->postJson('/api/meal-plans', [
        'starts_on' => $today->toDateString(),
        'ends_on' => $today->addDays(60)->toDateString(),
    ])->assertStatus(422)->assertJsonValidationErrors('ends_on');
});

test('a plan of a single day is allowed', function () {
    $user = User::factory()->create();
    $today = CarbonImmutable::today();

    $this->actingAs($user)->postJson('/api/meal-plans', [
        'starts_on' => $today->toDateString(),
        'ends_on' => $today->toDateString(),
    ])->assertStatus(201)->assertJsonPath('data.day_count', 1);
});

test('starting a new plan stands the previous one down', function () {
    $user = User::factory()->create();
    $today = CarbonImmutable::today();

    $first = $this->actingAs($user)->getJson('/api/meal-plan')->json('data.id');

    $second = $this->actingAs($user)->postJson('/api/meal-plans', [
        'name' => 'Next week',
        'starts_on' => $today->addDays(7)->toDateString(),
        'ends_on' => $today->addDays(13)->toDateString(),
    ])->json('data.id');

    expect(MealPlan::find($first)->is_active)->toBeFalse()
        ->and(MealPlan::find($second)->is_active)->toBeTrue();

    $this->actingAs($user)->getJson('/api/meal-plan')
        ->assertStatus(200)
        ->assertJsonPath('data.id', $second)
        ->assertJsonPath('data.name', 'Next week');
});

test('an older plan can be made current again', function () {
    $user = User::factory()->create();
    $past = MealPlan::factory()->past()->for($user)->create();
    $current = MealPlan::factory()->for($user)->create();

    $this->actingAs($user)->postJson("/api/meal-plans/{$past->id}/activate")
        ->assertStatus(200)
        ->assertJsonPath('data.id', $past->id)
        ->assertJsonPath('data.is_active', true);

    expect($current->refresh()->is_active)->toBeFalse();
});

test('the history lists the active plan first and the rest newest first', function () {
    $user = User::factory()->create();
    $today = CarbonImmutable::today();
    $recipe = Recipe::factory()->create(['cuisine' => 'Bangladeshi', 'servings' => 4]);

    $oldest = MealPlan::factory()->for($user)->startingOn($today->subDays(30))->create([
        'name' => 'Three weeks back',
        'is_active' => false,
    ]);
    $middle = MealPlan::factory()->for($user)->startingOn($today->subDays(10))->create([
        'name' => 'Last week',
        'is_active' => false,
    ]);
    $active = MealPlan::factory()->for($user)->startingOn($today->subDays(20))->create([
        'name' => 'Back on the menu',
        'is_active' => true,
    ]);

    MealPlanItem::factory()->for($active)->create([
        'recipe_id' => $recipe->id,
        'servings' => 6,
        'planned_for' => $today->subDays(20),
    ]);
    ShoppingListItem::create([
        'meal_plan_id' => $active->id,
        'display_name' => 'Onion',
        'quantity' => 3,
        'unit' => 'piece',
        'is_checked' => true,
    ]);
    ShoppingListItem::create([
        'meal_plan_id' => $active->id,
        'display_name' => 'Garlic',
        'quantity' => 2,
        'unit' => 'piece',
        'is_checked' => false,
    ]);

    $res = $this->actingAs($user)->getJson('/api/meal-plans');

    $res->assertStatus(200)
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('data.0.id', $active->id)
        ->assertJsonPath('data.1.id', $middle->id)
        ->assertJsonPath('data.2.id', $oldest->id)
        ->assertJsonPath('data.0.items_count', 1)
        ->assertJsonPath('data.0.servings_total', 6)
        ->assertJsonPath('data.0.cuisines', ['Bangladeshi'])
        ->assertJsonPath('data.0.day_count', 7)
        ->assertJsonPath('data.0.shopping_items_count', 2)
        ->assertJsonPath('data.0.shopping_checked_count', 1)
        ->assertJsonPath('data.1.items_count', 0)
        ->assertJsonPath('data.1.servings_total', 0)
        ->assertJsonPath('data.1.cuisines', []);
});

test('the history only ever shows the cook their own plans', function () {
    $user = User::factory()->create();
    $stranger = User::factory()->create();

    MealPlan::factory()->for($user)->create();
    MealPlan::factory()->for($stranger)->create();

    $this->actingAs($user)->getJson('/api/meal-plans')
        ->assertStatus(200)
        ->assertJsonCount(1, 'data');
});

test("someone else's plan is simply not there", function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $plan = MealPlan::factory()->for($owner)->create();

    $this->actingAs($stranger)->getJson("/api/meal-plans/{$plan->id}")->assertStatus(404);
    $this->actingAs($stranger)->patchJson("/api/meal-plans/{$plan->id}", ['name' => 'Mine now'])->assertStatus(404);
    $this->actingAs($stranger)->deleteJson("/api/meal-plans/{$plan->id}")->assertStatus(404);
    $this->actingAs($stranger)->postJson("/api/meal-plans/{$plan->id}/activate")->assertStatus(404);
    $this->actingAs($stranger)->getJson("/api/meal-plans/{$plan->id}/shopping-list")->assertStatus(404);
});

test('a past plan keeps its shopping list readable', function () {
    $user = User::factory()->create();
    $past = MealPlan::factory()->past()->for($user)->create();

    ShoppingListItem::create([
        'meal_plan_id' => $past->id,
        'display_name' => 'Mustard oil',
        'quantity' => 1,
        'unit' => 'litre',
        'is_checked' => true,
    ]);

    $this->actingAs($user)->getJson("/api/meal-plans/{$past->id}/shopping-list")
        ->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.display_name', 'Mustard oil')
        ->assertJsonPath('data.0.is_checked', true);

    $this->actingAs($user)->getJson("/api/meal-plans/{$past->id}")
        ->assertStatus(200)
        ->assertJsonPath('data.is_active', false);
});

test('a past plan refuses to be edited', function () {
    $user = User::factory()->create();
    $recipe = Recipe::factory()->create();
    $past = MealPlan::factory()->past()->for($user)->create();
    $item = MealPlanItem::factory()->for($past)->create([
        'recipe_id' => $recipe->id,
        'planned_for' => $past->starts_on,
    ]);

    $this->actingAs($user)->patchJson("/api/meal-plans/{$past->id}", ['name' => 'Rewritten'])
        ->assertStatus(403);

    $this->actingAs($user)->patchJson("/api/meal-plan/items/{$item->id}", ['servings' => 9])
        ->assertStatus(403);

    $this->actingAs($user)->deleteJson("/api/meal-plan/items/{$item->id}")
        ->assertStatus(403);
});

test('renaming and re-dating the current plan sets loose the dishes that fall outside', function () {
    $user = User::factory()->create();
    $today = CarbonImmutable::today();
    $recipe = Recipe::factory()->create();

    $plan = MealPlan::factory()->for($user)->startingOn($today, 10)->create();

    $stays = MealPlanItem::factory()->for($plan)->create([
        'recipe_id' => $recipe->id,
        'planned_for' => $today->addDay(),
    ]);
    $driftsOut = MealPlanItem::factory()->for($plan)->create([
        'recipe_id' => Recipe::factory()->create()->id,
        'planned_for' => $today->addDays(8),
    ]);

    $res = $this->actingAs($user)->patchJson("/api/meal-plans/{$plan->id}", [
        'name' => 'Short week',
        'ends_on' => $today->addDays(2)->toDateString(),
    ]);

    $res->assertStatus(200)
        ->assertJsonPath('data.name', 'Short week')
        ->assertJsonPath('data.ends_on', $today->addDays(2)->toDateString())
        ->assertJsonPath('data.day_count', 3)
        ->assertJsonPath('data.items_count', 2);

    expect($stays->refresh()->planned_for->toDateString())->toBe($today->addDay()->toDateString())
        ->and($driftsOut->refresh()->planned_for)->toBeNull();
});

test('a re-dated plan still has to make sense', function () {
    $user = User::factory()->create();
    $today = CarbonImmutable::today();
    $plan = MealPlan::factory()->for($user)->startingOn($today, 7)->create();

    $this->actingAs($user)->patchJson("/api/meal-plans/{$plan->id}", [
        'ends_on' => $today->subDay()->toDateString(),
    ])->assertStatus(422)->assertJsonValidationErrors('ends_on');

    $this->actingAs($user)->patchJson("/api/meal-plans/{$plan->id}", [
        'ends_on' => $today->addDays(90)->toDateString(),
    ])->assertStatus(422)->assertJsonValidationErrors('ends_on');
});

test('deleting the current plan leaves the cook with a fresh one', function () {
    $user = User::factory()->create();
    $plan = MealPlan::factory()->for($user)->create();

    $this->actingAs($user)->deleteJson("/api/meal-plans/{$plan->id}")->assertStatus(200);
    $this->assertDatabaseMissing('meal_plans', ['id' => $plan->id]);

    $res = $this->actingAs($user)->getJson('/api/meal-plan');

    $res->assertStatus(200)
        ->assertJsonPath('data.name', 'This week')
        ->assertJsonPath('data.starts_on', CarbonImmutable::today()->toDateString())
        ->assertJsonPath('data.ends_on', CarbonImmutable::today()->addDays(6)->toDateString());

    expect($res->json('data.id'))->not->toBe($plan->id);
});
