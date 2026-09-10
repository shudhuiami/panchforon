<?php

use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\RecipeStat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('public can list recipes with pagination, filters, and sorting', function () {
    $r1 = Recipe::factory()->create(['cuisine' => 'Bangladeshi', 'title' => 'Beef Kala Bhuna']);
    RecipeStat::create(['recipe_id' => $r1->id, 'bayesian_score' => 4.5]);

    $r2 = Recipe::factory()->create(['cuisine' => 'Italian', 'title' => 'Pasta Carbonara']);
    RecipeStat::create(['recipe_id' => $r2->id, 'bayesian_score' => 3.9]);

    // Filter by cuisine
    $res = $this->getJson('/api/recipes?cuisine=Bangladeshi');
    $res->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Beef Kala Bhuna');

    // Filter by search query
    $searchRes = $this->getJson('/api/recipes?q=Carbonara');
    $searchRes->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Pasta Carbonara');
});

test('public can view recipe detail by slug', function () {
    $recipe = Recipe::factory()->create(['slug' => 'bangladeshi-roast', 'title' => 'Bangladeshi Roast']);
    $ing = Ingredient::factory()->create(['canonical_name' => 'chicken breast']);
    RecipeIngredient::create([
        'recipe_id' => $recipe->id,
        'ingredient_id' => $ing->id,
        'quantity' => 500,
        'unit' => 'g',
        'raw_text' => '500g chicken breast',
        'position' => 0,
    ]);

    $res = $this->getJson('/api/recipes/bangladeshi-roast');

    $res->assertStatus(200)
        ->assertJsonPath('data.slug', 'bangladeshi-roast')
        ->assertJsonPath('data.title', 'Bangladeshi Roast')
        ->assertJsonCount(1, 'data.ingredients');
});

test('returns 404 for nonexistent recipe slug', function () {
    $res = $this->getJson('/api/recipes/non-existent-recipe');

    $res->assertStatus(404);
});

test('authenticated user can create a recipe with dynamic ingredients', function () {
    $user = User::factory()->create();

    $payload = [
        'title' => 'Spicy Masoor Dal',
        'cuisine' => 'Bangladeshi',
        'category' => 'Vegetarian',
        'instructions' => 'Boil dal with turmeric, temper with garlic and mustard oil.',
        'servings' => 4,
        'ingredients' => [
            ['raw_text' => '200g red lentils', 'quantity' => 200, 'unit' => 'g', 'name' => 'red lentils'],
            ['raw_text' => '1 onion, sliced', 'quantity' => 1, 'unit' => 'piece', 'name' => 'onion'],
            ['raw_text' => 'salt to taste', 'quantity' => null, 'unit' => null, 'name' => 'salt'],
        ],
    ];

    $res = $this->actingAs($user)->postJson('/api/recipes', $payload);

    $res->assertStatus(201)
        ->assertJsonPath('data.title', 'Spicy Masoor Dal')
        ->assertJsonCount(3, 'data.ingredients');

    $this->assertDatabaseHas('recipes', [
        'title' => 'Spicy Masoor Dal',
        'user_id' => $user->id,
    ]);
});

test('owner can update their recipe', function () {
    $user = User::factory()->create();
    $recipe = Recipe::factory()->create(['user_id' => $user->id, 'title' => 'Old Title']);

    $res = $this->actingAs($user)->putJson("/api/recipes/{$recipe->id}", [
        'title' => 'Updated Title',
        'instructions' => 'Updated instructions',
        'servings' => 6,
    ]);

    $res->assertStatus(200)
        ->assertJsonPath('data.title', 'Updated Title')
        ->assertJsonPath('data.servings', 6);
});

test('non-owner cannot update someone else recipe', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $recipe = Recipe::factory()->create(['user_id' => $owner->id]);

    $res = $this->actingAs($other)->putJson("/api/recipes/{$recipe->id}", [
        'title' => 'Hacked Title',
    ]);

    $res->assertStatus(403);
});

test('owner can delete their recipe', function () {
    $user = User::factory()->create();
    $recipe = Recipe::factory()->create(['user_id' => $user->id]);

    $res = $this->actingAs($user)->deleteJson("/api/recipes/{$recipe->id}");

    $res->assertStatus(200);
    $this->assertDatabaseMissing('recipes', ['id' => $recipe->id]);
});

test('non-owner cannot delete someone else recipe', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $recipe = Recipe::factory()->create(['user_id' => $owner->id]);

    $res = $this->actingAs($other)->deleteJson("/api/recipes/{$recipe->id}");

    $res->assertStatus(403);
    $this->assertDatabaseHas('recipes', ['id' => $recipe->id]);
});

test('public can retrieve distinct cuisines and categories with counts', function () {
    Recipe::factory()->create(['cuisine' => 'Bangladeshi', 'category' => 'Curry']);
    Recipe::factory()->create(['cuisine' => 'Bangladeshi', 'category' => 'Curry']);
    Recipe::factory()->create(['cuisine' => 'Italian', 'category' => 'Pasta']);

    $cuisinesRes = $this->getJson('/api/cuisines');
    $cuisinesRes->assertStatus(200)
        ->assertJsonStructure(['data' => [['cuisine', 'count']]]);

    $categoriesRes = $this->getJson('/api/categories');
    $categoriesRes->assertStatus(200)
        ->assertJsonStructure(['data' => [['category', 'count']]]);
});
