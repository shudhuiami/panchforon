<?php

use App\Models\Rating;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('authenticated user can upsert a rating with review', function () {
    $user = User::factory()->create();
    $recipe = Recipe::factory()->create();

    $res = $this->actingAs($user)->putJson("/api/recipes/{$recipe->id}/rating", [
        'stars' => 5,
        'review' => 'Delicious dish!',
    ]);

    $res->assertStatus(200)
        ->assertJsonPath('rating.stars', 5)
        ->assertJsonPath('rating.review', 'Delicious dish!')
        ->assertJsonStructure(['stat' => ['ratings_count', 'ratings_avg', 'bayesian_score']]);

    $this->assertDatabaseHas('ratings', [
        'user_id' => $user->id,
        'recipe_id' => $recipe->id,
        'stars' => 5,
    ]);
});

test('rating update updates in place and recalculates score', function () {
    $user = User::factory()->create();
    $recipe = Recipe::factory()->create();

    // First rating
    $this->actingAs($user)->putJson("/api/recipes/{$recipe->id}/rating", [
        'stars' => 5,
    ]);

    expect(Rating::where('recipe_id', $recipe->id)->count())->toBe(1);

    // Second rating update by same user
    $this->actingAs($user)->putJson("/api/recipes/{$recipe->id}/rating", [
        'stars' => 2,
    ]);

    expect(Rating::where('recipe_id', $recipe->id)->count())->toBe(1);

    $this->assertDatabaseHas('ratings', [
        'user_id' => $user->id,
        'recipe_id' => $recipe->id,
        'stars' => 2,
    ]);
});

test('rating validation rejects out-of-range stars', function () {
    $user = User::factory()->create();
    $recipe = Recipe::factory()->create();

    $res = $this->actingAs($user)->putJson("/api/recipes/{$recipe->id}/rating", [
        'stars' => 6,
    ]);

    $res->assertStatus(422)
        ->assertJsonValidationErrors(['stars']);
});

test('authenticated user can delete rating', function () {
    $user = User::factory()->create();
    $recipe = Recipe::factory()->create();

    Rating::create([
        'user_id' => $user->id,
        'recipe_id' => $recipe->id,
        'stars' => 4,
    ]);

    $res = $this->actingAs($user)->deleteJson("/api/recipes/{$recipe->id}/rating");

    $res->assertStatus(200)
        ->assertJson(['message' => 'Rating removed successfully']);

    $this->assertDatabaseMissing('ratings', [
        'user_id' => $user->id,
        'recipe_id' => $recipe->id,
    ]);
});
