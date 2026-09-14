<?php

use App\Enums\ModerationStatus;
use App\Models\Rating;
use App\Models\Recipe;
use App\Models\User;
use App\Services\SettingsRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * A recipe its author saved but has not submitted.
 *
 * @param  array<string, mixed>  $attributes
 */
function draftRecipe(array $attributes = []): Recipe
{
    return Recipe::factory()->create($attributes + ['moderation_status' => ModerationStatus::Draft]);
}

test('saving a recipe as a draft keeps it out of the moderation queue', function () {
    $author = User::factory()->create();

    $this->actingAs($author)->postJson('/api/recipes', [
        'status' => 'draft',
        'title' => 'Half-written Kachchi',
        'instructions' => 'Layer the rice over the mutton.',
        'ingredients' => [['raw_text' => '1 kg mutton']],
    ])->assertStatus(201)->assertJsonPath('data.moderation_status', 'draft');

    $recipe = Recipe::query()->where('title', 'Half-written Kachchi')->sole();

    expect($recipe->moderation_status)->toBe(ModerationStatus::Draft)
        ->and($recipe->user_id)->toBe($author->id)
        ->and(Recipe::query()->awaitingModeration()->count())->toBe(0);
});

test('posting a recipe still publishes by default', function () {
    $this->actingAs(User::factory()->create())->postJson('/api/recipes', [
        'title' => 'Doi Begun',
        'instructions' => 'Fry the aubergine, fold through yoghurt.',
        'ingredients' => [['raw_text' => '2 aubergines']],
    ])->assertStatus(201)->assertJsonPath('data.moderation_status', 'pending');
});

test('a status other than draft or published is rejected', function () {
    $this->actingAs(User::factory()->create())->postJson('/api/recipes', [
        'status' => 'approved',
        'title' => 'Sneaky Shortcut',
        'instructions' => 'Skip the queue.',
        'ingredients' => [['raw_text' => '1 pinch cheek']],
    ])->assertStatus(422)->assertJsonValidationErrors('status');
});

test('a draft is invisible to the public API', function () {
    $recipe = draftRecipe(['title' => 'Secret Bhorta', 'cuisine' => 'Klingon', 'category' => 'Gagh']);

    $this->getJson('/api/recipes')->assertOk()->assertJsonCount(0, 'data');
    $this->getJson('/api/recipes?q=Secret')->assertOk()->assertJsonCount(0, 'data');
    $this->getJson("/api/recipes/{$recipe->slug}")->assertStatus(404);

    $cuisines = collect($this->getJson('/api/cuisines')->json('data'))->pluck('cuisine');
    $categories = collect($this->getJson('/api/categories')->json('data'))->pluck('category');

    expect($cuisines)->not->toContain('Klingon')
        ->and($categories)->not->toContain('Gagh');
});

test('a draft is invisible to another signed-in cook', function () {
    $recipe = draftRecipe();

    $this->actingAs(User::factory()->create())
        ->getJson("/api/recipes/{$recipe->slug}")
        ->assertStatus(404);
});

test('a draft is readable by its author', function () {
    $author = User::factory()->create();
    $recipe = draftRecipe(['user_id' => $author->id]);

    $this->actingAs($author)
        ->getJson("/api/recipes/{$recipe->slug}")
        ->assertOk()
        ->assertJsonPath('data.id', $recipe->id)
        ->assertJsonPath('data.moderation_status', 'draft');
});

test('a draft stays off its author public cook page', function () {
    $author = User::factory()->create();
    draftRecipe(['user_id' => $author->id]);
    $published = Recipe::factory()->create(['user_id' => $author->id]);

    $response = $this->getJson("/api/cooks/{$author->id}")->assertOk();

    expect($response->json('data.cook.recipes_count'))->toBe(1)
        ->and(collect($response->json('data.recipes.data'))->pluck('id')->all())->toBe([$published->id]);
});

test('a draft is left out of the home feed and its counts', function () {
    draftRecipe(['cuisine' => 'Klingon']);
    $published = Recipe::factory()->create(['cuisine' => 'Bangladeshi']);
    Rating::factory()->create(['recipe_id' => $published->id]);

    $feed = $this->getJson('/api/home')->assertOk()->json('data');

    expect($feed['stats']['recipes'])->toBe(1)
        ->and(collect($feed['cuisines'])->pluck('cuisine')->all())->toBe(['Bangladeshi'])
        ->and(collect($feed['top_rated'])->pluck('id')->all())->toBe([$published->id])
        ->and(collect($feed['latest'])->pluck('id')->all())->toBe([$published->id]);
});

test('publishing a draft sends it to the review queue', function () {
    $author = User::factory()->create();
    $recipe = draftRecipe(['user_id' => $author->id]);

    $this->actingAs($author)
        ->postJson("/api/recipes/{$recipe->id}/publish")
        ->assertOk()
        ->assertJsonPath('data.moderation_status', 'pending');

    // Still not public: a moderator has to approve it, as with any submission.
    expect($recipe->fresh()->moderation_status)->toBe(ModerationStatus::Pending)
        ->and(Recipe::query()->awaitingModeration()->count())->toBe(1)
        ->and(Recipe::query()->publiclyVisible()->count())->toBe(0);
});

test('publishing someone else draft is refused', function () {
    $recipe = draftRecipe();

    $this->actingAs(User::factory()->create())
        ->postJson("/api/recipes/{$recipe->id}/publish")
        ->assertStatus(403);

    expect($recipe->fresh()->moderation_status)->toBe(ModerationStatus::Draft);
});

test('publishing needs a signed-in cook', function () {
    $this->postJson('/api/recipes/'.draftRecipe()->id.'/publish')->assertUnauthorized();
});

test('publishing something that is not a draft is a 422', function () {
    $author = User::factory()->create();
    $published = Recipe::factory()->create(['user_id' => $author->id]);

    $this->actingAs($author)
        ->postJson("/api/recipes/{$published->id}/publish")
        ->assertStatus(422);

    expect($published->fresh()->moderation_status)->toBe(ModerationStatus::Approved);
});

test('an author can publish a draft while saving the edit', function () {
    $author = User::factory()->create();
    $recipe = draftRecipe(['user_id' => $author->id, 'title' => 'Rough Notes']);

    $this->actingAs($author)->putJson("/api/recipes/{$recipe->id}", [
        'status' => 'published',
        'title' => 'Beef Kala Bhuna',
        'instructions' => 'Cook it down until the masala darkens.',
    ])->assertOk()->assertJsonPath('data.moderation_status', 'pending');

    expect($recipe->fresh()->moderation_status)->toBe(ModerationStatus::Pending);
});

test('editing a draft without a status leaves it a draft', function () {
    $author = User::factory()->create();
    $recipe = draftRecipe(['user_id' => $author->id]);

    $this->actingAs($author)
        ->putJson("/api/recipes/{$recipe->id}", ['title' => 'Still Rough'])
        ->assertOk()
        ->assertJsonPath('data.moderation_status', 'draft');

    expect($recipe->fresh()->moderation_status)->toBe(ModerationStatus::Draft);
});

test('editing a published recipe never demotes it to a draft', function () {
    $author = User::factory()->create();
    $recipe = Recipe::factory()->create(['user_id' => $author->id]);

    $this->actingAs($author)->putJson("/api/recipes/{$recipe->id}", [
        'status' => 'draft',
        'title' => 'Tidied Up',
    ])->assertOk()->assertJsonPath('data.moderation_status', 'approved');

    expect($recipe->fresh()->moderation_status)->toBe(ModerationStatus::Approved);

    $this->getJson('/api/recipes')->assertJsonCount(1, 'data');
});

test('editing a recipe in review leaves it in review', function () {
    $author = User::factory()->create();
    $recipe = Recipe::factory()->awaitingModeration()->create(['user_id' => $author->id]);

    $this->actingAs($author)
        ->putJson("/api/recipes/{$recipe->id}", ['status' => 'draft', 'title' => 'Tweaked'])
        ->assertOk()
        ->assertJsonPath('data.moderation_status', 'pending');

    expect($recipe->fresh()->moderation_status)->toBe(ModerationStatus::Pending);
});

test('a draft can still be saved while submissions are closed', function () {
    app(SettingsRepository::class)->set('submissions_open', false);

    $this->actingAs(User::factory()->create())->postJson('/api/recipes', [
        'status' => 'draft',
        'title' => 'Written In The Quiet',
        'instructions' => 'Wait for the queue to reopen.',
        'ingredients' => [['raw_text' => '1 tbsp patience']],
    ])->assertStatus(201)->assertJsonPath('data.moderation_status', 'draft');
});

test('publishing a draft while submissions are closed is refused', function () {
    app(SettingsRepository::class)->set('submissions_open', false);

    $author = User::factory()->create();
    $recipe = draftRecipe(['user_id' => $author->id]);

    $this->actingAs($author)->postJson("/api/recipes/{$recipe->id}/publish")->assertStatus(403);

    $this->actingAs($author)
        ->putJson("/api/recipes/{$recipe->id}", ['status' => 'published'])
        ->assertStatus(403);

    expect($recipe->fresh()->moderation_status)->toBe(ModerationStatus::Draft);
});
