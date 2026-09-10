<?php

use App\Enums\ModerationStatus;
use App\Models\Recipe;
use App\Models\User;
use App\Services\SettingsRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the public recipe list only returns approved recipes', function () {
    Recipe::factory()->create(['title' => 'Approved Bhuna']);
    Recipe::factory()->awaitingModeration()->create(['title' => 'Pending Bhuna']);
    Recipe::factory()->unpublished()->create(['title' => 'Hidden Bhuna']);

    $response = $this->getJson('/api/recipes');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Approved Bhuna');
});

test('an unapproved recipe is a 404 for the public', function () {
    $pending = Recipe::factory()->awaitingModeration()->create();
    $unpublished = Recipe::factory()->unpublished()->create();

    $this->getJson("/api/recipes/{$pending->slug}")->assertStatus(404);
    $this->getJson("/api/recipes/{$unpublished->slug}")->assertStatus(404);
});

test('an author can still read their own recipe while it awaits review', function () {
    $author = User::factory()->create();
    $recipe = Recipe::factory()->awaitingModeration()->for($author)->create();

    $this->actingAs($author)
        ->getJson("/api/recipes/{$recipe->slug}")
        ->assertStatus(200)
        ->assertJsonPath('data.id', $recipe->id);
});

test('another user cannot read a recipe that awaits review', function () {
    $recipe = Recipe::factory()->awaitingModeration()->create();

    $this->actingAs(User::factory()->create())
        ->getJson("/api/recipes/{$recipe->slug}")
        ->assertStatus(404);
});

test('an admin can read any recipe regardless of moderation state', function () {
    $recipe = Recipe::factory()->unpublished()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->getJson("/api/recipes/{$recipe->slug}")
        ->assertStatus(200)
        ->assertJsonPath('data.id', $recipe->id);
});

test('a newly submitted recipe goes into the moderation queue', function () {
    $response = $this->actingAs(User::factory()->create())->postJson('/api/recipes', [
        'title' => 'Shorshe Ilish',
        'instructions' => 'Steam the hilsa with mustard paste.',
        'ingredients' => [
            ['raw_text' => '500 g hilsa'],
            ['raw_text' => '3 tbsp mustard paste'],
        ],
    ]);

    $response->assertStatus(201);

    $recipe = Recipe::query()->where('title', 'Shorshe Ilish')->sole();

    expect($recipe->moderation_status)->toBe(ModerationStatus::Pending);

    $this->getJson('/api/recipes?q=Shorshe')->assertJsonCount(0, 'data');
});

test('cuisine and category facets exclude unapproved recipes', function () {
    Recipe::factory()->create(['cuisine' => 'Bangladeshi', 'category' => 'Fish']);
    Recipe::factory()->awaitingModeration()->create(['cuisine' => 'Klingon', 'category' => 'Gagh']);

    $cuisines = collect($this->getJson('/api/cuisines')->json('data'))->pluck('cuisine');
    $categories = collect($this->getJson('/api/categories')->json('data'))->pluck('category');

    expect($cuisines)->toContain('Bangladeshi')->not->toContain('Klingon')
        ->and($categories)->toContain('Fish')->not->toContain('Gagh');
});

test('closing registration blocks the register endpoint', function () {
    app(SettingsRepository::class)->set('registration_open', false);

    $this->postJson('/api/register', [
        'name' => 'Rumi',
        'email' => 'rumi@example.com',
        'password' => 'password123',
    ])->assertStatus(403);

    expect(User::query()->where('email', 'rumi@example.com')->exists())->toBeFalse();
});

test('closing submissions blocks the recipe store endpoint', function () {
    app(SettingsRepository::class)->set('submissions_open', false);

    $this->actingAs(User::factory()->create())->postJson('/api/recipes', [
        'title' => 'Panta Bhat',
        'instructions' => 'Soak rice overnight.',
        'ingredients' => [['raw_text' => '2 cups rice']],
    ])->assertStatus(403);

    expect(Recipe::query()->where('title', 'Panta Bhat')->exists())->toBeFalse();
});

test('registration and submissions are open by default', function () {
    $this->postJson('/api/register', [
        'name' => 'Nadia',
        'email' => 'nadia@example.com',
        'password' => 'password123',
    ])->assertStatus(201);

    $this->actingAs(User::factory()->create())->postJson('/api/recipes', [
        'title' => 'Chingri Malai Curry',
        'instructions' => 'Simmer prawns in coconut milk.',
        'ingredients' => [['raw_text' => '400 g prawns']],
    ])->assertStatus(201);
});
