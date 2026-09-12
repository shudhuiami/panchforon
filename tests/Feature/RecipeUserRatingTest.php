<?php

use App\Models\Rating;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('recipe detail reports the bearer user\'s own rating', function () {
    $user = User::factory()->create();
    $recipe = Recipe::factory()->create();
    Rating::factory()->create(['user_id' => $user->getKey(), 'recipe_id' => $recipe->getKey(), 'stars' => 4]);
    $token = $user->createToken('auth-token')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson("/api/recipes/{$recipe->slug}")
        ->assertStatus(200)
        ->assertJsonPath('data.user_rating', 4);
});

test('recipe detail has no user rating for guests', function () {
    $recipe = Recipe::factory()->create();
    Rating::factory()->create(['recipe_id' => $recipe->getKey(), 'stars' => 5]);

    $this->getJson("/api/recipes/{$recipe->slug}")
        ->assertStatus(200)
        ->assertJsonPath('data.user_rating', null);
});
