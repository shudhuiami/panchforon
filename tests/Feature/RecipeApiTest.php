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
    $user = User::factory()->creator()->create();

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

test('a recipe is created with its bangla name, cooking times, spice level and per-row ingredient detail', function () {
    $user = User::factory()->creator()->create();

    $res = $this->actingAs($user)->postJson('/api/recipes', [
        'title' => 'Bangladeshi Chicken Curry',
        'name_bn' => 'মুরগির ঝোল',
        'cuisine' => 'Bangladeshi',
        'category' => 'Chicken',
        'instructions' => 'Fry the onions, add the chicken, simmer until the oil separates.',
        'servings' => 4,
        'prep_minutes' => 20,
        'cook_minutes' => 45,
        'spice_level' => 'medium',
        'ingredients' => [
            ['name' => 'chicken', 'quantity' => 1000, 'unit' => 'g', 'raw_text' => '1000 g chicken, curry cut', 'note' => 'About 8-10 pieces'],
            ['name' => 'yogurt', 'quantity' => 100, 'unit' => 'g', 'raw_text' => '100 g yogurt', 'is_optional' => true],
        ],
    ]);

    $res->assertStatus(201)
        ->assertJsonPath('data.name_bn', 'মুরগির ঝোল')
        ->assertJsonPath('data.prep_minutes', 20)
        ->assertJsonPath('data.cook_minutes', 45)
        ->assertJsonPath('data.total_minutes', 65)
        ->assertJsonPath('data.spice_level', 'medium')
        ->assertJsonPath('data.ingredients.0.is_optional', false)
        ->assertJsonPath('data.ingredients.0.note', 'About 8-10 pieces')
        ->assertJsonPath('data.ingredients.1.is_optional', true)
        ->assertJsonPath('data.ingredients.1.note', null);

    $this->assertDatabaseHas('recipes', [
        'title' => 'Bangladeshi Chicken Curry',
        'name_bn' => 'মুরগির ঝোল',
        'prep_minutes' => 20,
        'cook_minutes' => 45,
        'spice_level' => 'medium',
    ]);

    $this->assertDatabaseHas('recipe_ingredients', [
        'raw_text' => '100 g yogurt',
        'is_optional' => true,
    ]);
});

test('an owner can add the cooking metadata to a recipe that was saved without it', function () {
    $user = User::factory()->create();
    $recipe = Recipe::factory()->create([
        'user_id' => $user->id,
        'prep_minutes' => null,
        'cook_minutes' => null,
        'spice_level' => null,
    ]);

    $res = $this->actingAs($user)->putJson("/api/recipes/{$recipe->id}", [
        'name_bn' => 'সর্ষে ইলিশ',
        'prep_minutes' => 15,
        'cook_minutes' => 25,
        'spice_level' => 'hot',
        'ingredients' => [
            ['name' => 'hilsa', 'quantity' => 600, 'unit' => 'g', 'raw_text' => '600 g hilsa', 'is_optional' => false],
            ['name' => 'green chilli', 'quantity' => 4, 'unit' => 'piece', 'raw_text' => '4 green chillies', 'is_optional' => true, 'note' => 'Slit, added at the end'],
        ],
    ]);

    $res->assertStatus(200)
        ->assertJsonPath('data.name_bn', 'সর্ষে ইলিশ')
        ->assertJsonPath('data.prep_minutes', 15)
        ->assertJsonPath('data.cook_minutes', 25)
        ->assertJsonPath('data.total_minutes', 40)
        ->assertJsonPath('data.spice_level', 'hot')
        ->assertJsonPath('data.ingredients.1.is_optional', true)
        ->assertJsonPath('data.ingredients.1.note', 'Slit, added at the end');

    $this->assertDatabaseHas('recipes', [
        'id' => $recipe->id,
        'name_bn' => 'সর্ষে ইলিশ',
        'spice_level' => 'hot',
    ]);
});

test('the public detail and list endpoints carry the cooking metadata', function () {
    $recipe = Recipe::factory()->create([
        'slug' => 'shorshe-ilish',
        'title' => 'Shorshe Ilish',
        'name_bn' => 'সর্ষে ইলিশ',
        'prep_minutes' => 15,
        'cook_minutes' => 25,
        'spice_level' => 'hot',
    ]);

    $ingredient = Ingredient::factory()->create([
        'canonical_name' => 'hilsa',
        'name_bn' => 'ইলিশ',
        'is_shoppable' => true,
        'preferred_unit' => 'g',
    ]);

    RecipeIngredient::create([
        'recipe_id' => $recipe->id,
        'ingredient_id' => $ingredient->id,
        'quantity' => 600,
        'unit' => 'g',
        'is_optional' => true,
        'note' => 'Skin on',
        'raw_text' => '600 g hilsa',
        'position' => 0,
    ]);

    $this->getJson('/api/recipes/shorshe-ilish')
        ->assertStatus(200)
        ->assertJsonPath('data.name_bn', 'সর্ষে ইলিশ')
        ->assertJsonPath('data.prep_minutes', 15)
        ->assertJsonPath('data.cook_minutes', 25)
        ->assertJsonPath('data.total_minutes', 40)
        ->assertJsonPath('data.spice_level', 'hot')
        ->assertJsonPath('data.ingredients.0.is_optional', true)
        ->assertJsonPath('data.ingredients.0.note', 'Skin on')
        ->assertJsonPath('data.ingredients.0.ingredient.name_bn', 'ইলিশ')
        ->assertJsonPath('data.ingredients.0.ingredient.is_shoppable', true)
        ->assertJsonPath('data.ingredients.0.ingredient.preferred_unit', 'g');

    $this->getJson('/api/recipes?q=Shorshe')
        ->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name_bn', 'সর্ষে ইলিশ')
        ->assertJsonPath('data.0.prep_minutes', 15)
        ->assertJsonPath('data.0.cook_minutes', 25)
        ->assertJsonPath('data.0.total_minutes', 40)
        ->assertJsonPath('data.0.spice_level', 'hot');
});

test('total_minutes reports whichever time is known, and nothing when neither is', function () {
    Recipe::factory()->create(['slug' => 'prep-only', 'prep_minutes' => 20, 'cook_minutes' => null]);
    Recipe::factory()->create(['slug' => 'cook-only', 'prep_minutes' => null, 'cook_minutes' => 45]);
    Recipe::factory()->create(['slug' => 'neither', 'prep_minutes' => null, 'cook_minutes' => null]);

    $this->getJson('/api/recipes/prep-only')
        ->assertStatus(200)
        ->assertJsonPath('data.total_minutes', 20);

    $this->getJson('/api/recipes/cook-only')
        ->assertStatus(200)
        ->assertJsonPath('data.total_minutes', 45);

    $this->getJson('/api/recipes/neither')
        ->assertStatus(200)
        ->assertJsonPath('data.total_minutes', null)
        ->assertJsonPath('data.prep_minutes', null)
        ->assertJsonPath('data.cook_minutes', null);
});

test('an unknown spice level, a negative time and an implausible one are all rejected', function () {
    $user = User::factory()->creator()->create();

    $this->actingAs($user)->postJson('/api/recipes', [
        'title' => 'Nuclear Vindaloo',
        'instructions' => 'Add every chilli in the house.',
        'prep_minutes' => -5,
        'cook_minutes' => 5000,
        'spice_level' => 'nuclear',
        'ingredients' => [['raw_text' => '10 chillies']],
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['prep_minutes', 'cook_minutes', 'spice_level']);

    $recipe = Recipe::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)->putJson("/api/recipes/{$recipe->id}", [
        'prep_minutes' => -1,
        'spice_level' => 'volcanic',
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['prep_minutes', 'spice_level']);
});

test('an ingredient unit that matches no known unit is stored rather than rejected', function () {
    $user = User::factory()->creator()->create();

    $res = $this->actingAs($user)->postJson('/api/recipes', [
        'title' => 'Imported Kitchen Sink',
        'instructions' => 'Whatever the source file said.',
        'ingredients' => [
            ['name' => 'coriander', 'quantity' => 1, 'unit' => 'handful', 'raw_text' => '1 handful coriander'],
        ],
    ]);

    $res->assertStatus(201)
        ->assertJsonPath('data.ingredients.0.unit', 'handful');

    $this->assertDatabaseHas('recipe_ingredients', [
        'raw_text' => '1 handful coriander',
        'unit' => 'handful',
    ]);
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
