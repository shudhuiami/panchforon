<?php

use App\Models\Recipe;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the detail payload carries the video id', function () {
    Recipe::factory()->withVideo('dQw4w9WgXcQ')->create(['slug' => 'shorshe-ilish']);

    $this->getJson('/api/recipes/shorshe-ilish')
        ->assertOk()
        ->assertJsonPath('data.youtube_video_id', 'dQw4w9WgXcQ');
});

test('the detail payload reports null for a recipe without a video', function () {
    Recipe::factory()->create(['slug' => 'plain-dal']);

    $this->getJson('/api/recipes/plain-dal')
        ->assertOk()
        ->assertJsonPath('data.youtube_video_id', null);
});

test('the list payload says whether there is a video without carrying the id', function () {
    Recipe::factory()->withVideo()->create(['cuisine' => 'Bangladeshi']);
    Recipe::factory()->create(['cuisine' => 'Italian']);

    $withVideo = $this->getJson('/api/recipes?cuisine=Bangladeshi')->assertOk();
    $withVideo->assertJsonPath('data.0.has_video', true);

    $this->getJson('/api/recipes?cuisine=Italian')
        ->assertOk()
        ->assertJsonPath('data.0.has_video', false);

    expect($withVideo->json('data.0'))->not->toHaveKey('youtube_video_id');
});

/**
 * The id goes straight into an iframe src, so the payload is the last place it
 * can be checked. Anything that is neither null nor exactly eleven id
 * characters would let a stored value steer the embed somewhere other than
 * YouTube, and the list's boolean has to agree with what the detail serves.
 */
test('a video id in a payload is always an id or null', function (?string $stored) {
    $recipe = Recipe::factory()->create(['slug' => 'checked-recipe']);
    $recipe->forceFill(['youtube_video_id' => $stored])->save();

    $served = $this->getJson('/api/recipes/checked-recipe')->assertOk()->json('data.youtube_video_id');

    expect($served === null || preg_match('/^[A-Za-z0-9_-]{11}$/', (string) $served) === 1)->toBeTrue();

    $listed = $this->getJson('/api/recipes')->assertOk()->json('data.0.has_video');

    expect($listed)->toBeBool()->toBe($served !== null);
})->with([
    'an id' => 'dQw4w9WgXcQ',
    'nothing stored' => null,
    'an empty string' => '',
    'a whole watch url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
    'a link somewhere else' => 'https://vimeo.com/76979871',
]);
