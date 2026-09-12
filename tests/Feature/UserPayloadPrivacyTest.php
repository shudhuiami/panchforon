<?php

use App\Models\Rating;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the current user payload carries role and counts', function () {
    $admin = User::factory()->admin()->create();
    Recipe::factory()->count(2)->for($admin)->create();

    $this->actingAs($admin)->getJson('/api/user')
        ->assertStatus(200)
        ->assertJsonPath('data.is_admin', true)
        ->assertJsonPath('data.recipes_count', 2)
        ->assertJsonPath('data.ratings_count', 0)
        ->assertJsonPath('data.email', $admin->email);

    $this->actingAs(User::factory()->create())->getJson('/api/user')
        ->assertJsonPath('data.is_admin', false);
});

test('login and register responses use the private payload', function () {
    $user = User::factory()->create(['password' => 'password']);

    $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password'])
        ->assertStatus(200)
        ->assertJsonPath('user.is_admin', false)
        ->assertJsonPath('user.email', $user->email);
});

test('a recipe author is public without an email address or role', function () {
    $author = User::factory()->admin()->create();
    $recipe = Recipe::factory()->for($author)->create();

    $json = $this->getJson("/api/recipes/{$recipe->slug}")->assertStatus(200)->json('data.author');

    expect($json)->toHaveKeys(['id', 'name'])
        ->and($json)->not->toHaveKey('email')
        ->and($json)->not->toHaveKey('is_admin');
});

test('a reviewer is public without an email address', function () {
    $recipe = Recipe::factory()->create();
    Rating::factory()->create(['recipe_id' => $recipe->getKey()]);

    $reviewer = $this->getJson("/api/recipes/{$recipe->slug}")->json('data.ratings.0.user');

    expect($reviewer)->toHaveKeys(['id', 'name'])
        ->and($reviewer)->not->toHaveKey('email');
});

test('recipe payloads expose moderation status', function () {
    $recipe = Recipe::factory()->create();

    $this->getJson('/api/recipes')->assertJsonPath('data.0.moderation_status', 'approved');
    $this->getJson("/api/recipes/{$recipe->slug}")->assertJsonPath('data.moderation_status', 'approved');
});
