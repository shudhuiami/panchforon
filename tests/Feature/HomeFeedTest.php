<?php

use App\Enums\ModerationStatus;
use App\Models\Rating;
use App\Models\Recipe;
use App\Models\RecipeStat;
use App\Models\User;
use App\Services\HomeFeed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

test('the home feed bundles stats, the featured pick, rankings and cuisines', function () {
    $bengali = Recipe::factory()->count(3)->create(['cuisine' => 'Bangladeshi', 'image_url' => 'https://img.test/bhuna.jpg']);
    Recipe::factory()->count(2)->create(['cuisine' => 'Thai', 'image_url' => null]);
    RecipeStat::factory()->create(['recipe_id' => $bengali[0]->id, 'bayesian_score' => 4.9, 'ratings_avg' => 5.0, 'ratings_count' => 2]);
    Rating::factory()->count(2)->create(['recipe_id' => $bengali[0]->id, 'stars' => 5]);

    $json = $this->getJson('/api/home')->assertOk()->json('data');

    expect($json['stats'])->toBe([
        'recipes' => 5,
        'cuisines' => 2,
        'ratings' => 2,
        'cooks' => User::count(),
    ])
        ->and($json['featured']['id'])->toBe($bengali[0]->id)
        ->and($json['top_rated'][0]['id'])->toBe($bengali[0]->id)
        ->and($json['top_rated'])->toHaveCount(5)
        ->and($json['latest'])->toHaveCount(4)
        ->and($json['cuisines'])->toBe([
            ['cuisine' => 'Bangladeshi', 'count' => 3, 'image_url' => 'https://img.test/bhuna.jpg'],
            ['cuisine' => 'Thai', 'count' => 2, 'image_url' => null],
        ]);
});

test('recipes awaiting moderation or unpublished never reach the home feed', function () {
    $visible = Recipe::factory()->create(['cuisine' => 'Italian']);
    Recipe::factory()->awaitingModeration()->create(['cuisine' => 'Pending']);
    Recipe::factory()->unpublished()->create(['cuisine' => 'Unpublished']);

    $json = $this->getJson('/api/home')->json('data');

    expect($json['stats']['recipes'])->toBe(1)
        ->and(collect($json['top_rated'])->pluck('id')->all())->toBe([$visible->id])
        ->and(collect($json['latest'])->pluck('id')->all())->toBe([$visible->id])
        ->and(collect($json['cuisines'])->pluck('cuisine')->all())->toBe(['Italian']);
});

test('the home feed is cached and refreshed the moment a recipe is added', function () {
    Recipe::factory()->create();

    $this->getJson('/api/home')->assertJsonPath('data.stats.recipes', 1);

    expect(Cache::has(HomeFeed::CACHE_KEY))->toBeTrue();

    Recipe::factory()->create();

    $this->getJson('/api/home')->assertJsonPath('data.stats.recipes', 2);
});

test('unpublishing a recipe drops it from the cached feed at once', function () {
    $recipe = Recipe::factory()->create();
    $this->getJson('/api/home')->assertJsonPath('data.stats.recipes', 1);

    $recipe->update(['moderation_status' => ModerationStatus::Unpublished]);

    $this->getJson('/api/home')
        ->assertJsonPath('data.stats.recipes', 0)
        ->assertJsonPath('data.featured', null);
});
