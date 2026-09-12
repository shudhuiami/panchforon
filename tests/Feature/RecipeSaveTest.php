<?php

use App\Enums\ModerationStatus;
use App\Models\Recipe;
use App\Models\RecipeSave;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('saving a recipe keeps it on the cook’s list', function () {
    $user = User::factory()->create();
    $recipe = Recipe::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson("/api/recipes/{$recipe->id}/save")
        ->assertCreated()
        ->assertJsonPath('data.is_saved', true);

    expect($user->savedRecipes()->pluck('recipes.id')->all())->toBe([$recipe->id]);
});

test('saving the same recipe twice is the same as saving it once', function () {
    $user = User::factory()->create();
    $recipe = Recipe::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson("/api/recipes/{$recipe->id}/save")->assertCreated();
    $this->postJson("/api/recipes/{$recipe->id}/save")->assertCreated();

    expect(RecipeSave::count())->toBe(1);
});

test('unsaving removes it and says so', function () {
    $user = User::factory()->create();
    $recipe = Recipe::factory()->create();
    RecipeSave::factory()->create(['user_id' => $user->id, 'recipe_id' => $recipe->id]);
    Sanctum::actingAs($user);

    $this->deleteJson("/api/recipes/{$recipe->id}/save")
        ->assertOk()
        ->assertJsonPath('data.is_saved', false);

    expect(RecipeSave::count())->toBe(0);
});

test('a recipe that is not publicly visible cannot be saved', function () {
    $user = User::factory()->create();
    $hidden = Recipe::factory()->awaitingModeration()->create();
    Sanctum::actingAs($user);

    $this->postJson("/api/recipes/{$hidden->id}/save")->assertNotFound();

    expect(RecipeSave::count())->toBe(0);
});

test('the saved list only returns recipes the catalogue still shows', function () {
    $user = User::factory()->create();
    $visible = Recipe::factory()->create();
    $pulled = Recipe::factory()->create();
    RecipeSave::factory()->create(['user_id' => $user->id, 'recipe_id' => $visible->id]);
    RecipeSave::factory()->create(['user_id' => $user->id, 'recipe_id' => $pulled->id]);
    $pulled->update(['moderation_status' => ModerationStatus::Unpublished]);
    Sanctum::actingAs($user);

    $json = $this->getJson('/api/saved-recipes')->assertOk()->json('data');

    expect(collect($json)->pluck('id')->all())->toBe([$visible->id]);
});

test('one cook never sees another cook’s saves', function () {
    $mine = User::factory()->create();
    $theirs = User::factory()->create();
    $recipe = Recipe::factory()->create();
    RecipeSave::factory()->create(['user_id' => $theirs->id, 'recipe_id' => $recipe->id]);
    Sanctum::actingAs($mine);

    $this->getJson('/api/saved-recipes')->assertOk()->assertJsonCount(0, 'data');
    $this->getJson('/api/saved-recipes/ids')->assertOk()->assertExactJson(['data' => []]);
});

test('the id list lets the client mark every heart in one request', function () {
    $user = User::factory()->create();
    $recipes = Recipe::factory()->count(2)->create();
    foreach ($recipes as $recipe) {
        RecipeSave::factory()->create(['user_id' => $user->id, 'recipe_id' => $recipe->id]);
    }
    Sanctum::actingAs($user);

    $ids = $this->getJson('/api/saved-recipes/ids')->assertOk()->json('data');

    expect($ids)->toEqualCanonicalizing($recipes->pluck('id')->all());
});

test('saving requires a signed-in cook', function () {
    $recipe = Recipe::factory()->create();

    $this->postJson("/api/recipes/{$recipe->id}/save")->assertUnauthorized();
    $this->getJson('/api/saved-recipes')->assertUnauthorized();
});

test('a suspended cook cannot save recipes', function () {
    $user = User::factory()->create(['suspended_at' => now(), 'suspension_reason' => 'Spam']);
    $recipe = Recipe::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson("/api/recipes/{$recipe->id}/save")
        ->assertForbidden()
        ->assertJsonPath('code', 'account_suspended');
});
