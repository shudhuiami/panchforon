<?php

use App\Enums\ModerationStatus;
use App\Models\Recipe;
use App\Models\User;
use App\Services\SettingsRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * A recipe payload the store endpoint accepts, so each test only has to say
 * what it is changing.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function recipePayload(array $overrides = []): array
{
    return $overrides + [
        'title' => 'Aloo Bhorta',
        'instructions' => 'Mash the potato with mustard oil, onion and green chilli.',
        'ingredients' => [['raw_text' => '3 potatoes']],
    ];
}

test('a member cannot post a recipe and is told how to become a creator', function () {
    $response = $this->actingAs(User::factory()->create())
        ->postJson('/api/recipes', recipePayload());

    $response->assertStatus(403);

    expect($response->json('message'))->toContain('creator')
        ->and(Recipe::query()->count())->toBe(0);
});

test('a member is pointed at the application even while submissions are closed', function () {
    app(SettingsRepository::class)->set('submissions_open', false);

    $response = $this->actingAs(User::factory()->create())
        ->postJson('/api/recipes', recipePayload());

    $response->assertStatus(403);

    expect($response->json('message'))->toContain('creator');
});

test('a member cannot sidestep the role by saving a draft', function () {
    $this->actingAs(User::factory()->create())
        ->postJson('/api/recipes', recipePayload(['status' => 'draft']))
        ->assertStatus(403);

    expect(Recipe::query()->count())->toBe(0);
});

test('a creator recipe is published the moment it is saved', function () {
    $creator = User::factory()->creator()->create();

    $this->actingAs($creator)
        ->postJson('/api/recipes', recipePayload())
        ->assertStatus(201)
        ->assertJsonPath('data.moderation_status', 'approved');

    $recipe = Recipe::query()->sole();

    expect($recipe->moderation_status)->toBe(ModerationStatus::Approved)
        ->and(Recipe::query()->awaitingModeration()->count())->toBe(0);

    $this->getJson('/api/recipes')->assertJsonCount(1, 'data');
});

/**
 * Those two columns say an admin made a decision about the recipe. Nobody did,
 * so leaving them set would put a moderator's name against a review that never
 * happened.
 */
test('an auto-published recipe records no moderator', function () {
    $this->actingAs(User::factory()->creator()->create())
        ->postJson('/api/recipes', recipePayload())
        ->assertStatus(201);

    $recipe = Recipe::query()->sole();

    expect($recipe->moderated_at)->toBeNull()
        ->and($recipe->moderated_by)->toBeNull();
});

test('an admin posts without review as well', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->postJson('/api/recipes', recipePayload())
        ->assertStatus(201)
        ->assertJsonPath('data.moderation_status', 'approved');
});

test('a suspended creator cannot post at all', function () {
    $this->actingAs(User::factory()->creator()->suspended()->create())
        ->postJson('/api/recipes', recipePayload())
        ->assertStatus(403)
        ->assertJsonPath('code', 'account_suspended');

    expect(Recipe::query()->count())->toBe(0);
});

test('a creator draft is still only a draft', function () {
    $creator = User::factory()->creator()->create();

    $this->actingAs($creator)
        ->postJson('/api/recipes', recipePayload(['status' => 'draft']))
        ->assertStatus(201)
        ->assertJsonPath('data.moderation_status', 'draft');

    expect(Recipe::query()->sole()->moderation_status)->toBe(ModerationStatus::Draft);

    $this->getJson('/api/recipes')->assertJsonCount(0, 'data');
});

/**
 * The role gates writing new recipes, not keeping the ones already written. A
 * member who posted before the role existed still owns and edits them.
 */
test('a member can still edit the recipe they posted before the role existed', function () {
    $member = User::factory()->create();
    $recipe = Recipe::factory()->create(['user_id' => $member->id, 'title' => 'Old Bhuna']);

    $this->actingAs($member)
        ->putJson("/api/recipes/{$recipe->id}", [
            'title' => 'Old Bhuna, Tidied',
            'instructions' => 'Cook it down until the masala darkens.',
        ])
        ->assertOk()
        ->assertJsonPath('data.title', 'Old Bhuna, Tidied')
        ->assertJsonPath('data.moderation_status', 'approved');
});

test('a member cannot publish a draft of their own', function () {
    $member = User::factory()->create();
    $draft = Recipe::factory()->create([
        'user_id' => $member->id,
        'moderation_status' => ModerationStatus::Draft,
    ]);

    $this->actingAs($member)
        ->postJson("/api/recipes/{$draft->id}/publish")
        ->assertStatus(403)
        ->assertJsonPath('message', 'Only creators can publish recipes. Apply to become a creator to share yours.');

    $this->actingAs($member)
        ->putJson("/api/recipes/{$draft->id}", ['status' => 'published', 'title' => 'Ready Now'])
        ->assertStatus(403);

    expect($draft->fresh()->moderation_status)->toBe(ModerationStatus::Draft);
});

test('a member can keep editing their draft without publishing it', function () {
    $member = User::factory()->create();
    $draft = Recipe::factory()->create([
        'user_id' => $member->id,
        'moderation_status' => ModerationStatus::Draft,
    ]);

    $this->actingAs($member)
        ->putJson("/api/recipes/{$draft->id}", ['title' => 'Still Rough'])
        ->assertOk()
        ->assertJsonPath('data.moderation_status', 'draft');
});
