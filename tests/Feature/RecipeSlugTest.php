<?php

use App\Enums\ModerationStatus;
use App\Filament\Studio\Resources\Recipes\Pages\CreateRecipe;
use App\Models\Recipe;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * Recipe::uniqueSlugFor() is the single rule for turning a title into a URL.
 *
 * It exists because there used to be three: one in the store endpoint, a
 * different one in update, and the studio was about to grow a third. A slug is
 * the recipe's public address, so two doors producing two different addresses
 * for the same title is not a cosmetic difference.
 */
test('a free title keeps its plain slug', function () {
    expect(Recipe::uniqueSlugFor('Kosha Mangsho'))->toBe('kosha-mangsho');
});

test('a taken title counts up until the slug is free', function () {
    Recipe::factory()->create(['slug' => 'kosha-mangsho']);
    expect(Recipe::uniqueSlugFor('Kosha Mangsho'))->toBe('kosha-mangsho-2');

    Recipe::factory()->create(['slug' => 'kosha-mangsho-2']);
    expect(Recipe::uniqueSlugFor('Kosha Mangsho'))->toBe('kosha-mangsho-3');
});

/**
 * The bug the old counting rule carried: it counted slugs starting with the
 * base and appended count + 1, so a catalogue holding "kosha-mangsho-2" but
 * not "kosha-mangsho" counted one match and proposed "kosha-mangsho-2" again,
 * straight into the unique index.
 */
test('a suffix that exists without its base does not collide', function () {
    Recipe::factory()->create(['slug' => 'kosha-mangsho-2']);

    expect(Recipe::uniqueSlugFor('Kosha Mangsho'))->toBe('kosha-mangsho');
});

test('a recipe renamed to the title it already has keeps its slug', function () {
    $recipe = Recipe::factory()->create(['slug' => 'kosha-mangsho']);

    expect(Recipe::uniqueSlugFor('Kosha Mangsho', $recipe->id))->toBe('kosha-mangsho')
        ->and(Recipe::uniqueSlugFor('Kosha Mangsho'))->toBe('kosha-mangsho-2');
});

test('a title that slugs to nothing still gets an address', function () {
    expect(Recipe::uniqueSlugFor('।।।'))->toBe('recipe');
});

test('the API and the studio produce the same slug for the same title', function () {
    $title = 'Ilish Bhapa With Mustard';

    $viaApi = User::factory()->creator()->create();

    $this->actingAs($viaApi)
        ->postJson('/api/recipes', [
            'title' => $title,
            'instructions' => 'Steam it.',
            'ingredients' => [['name' => 'hilsa', 'quantity' => 4, 'unit' => 'piece']],
        ])
        ->assertCreated();

    $apiSlug = Recipe::where('user_id', $viaApi->id)->value('slug');

    /** Same title, second author, through the studio's create screen instead. */
    $viaStudio = User::factory()->creator()->create();

    Filament::setCurrentPanel('studio');
    $this->actingAs($viaStudio);

    Livewire::test(CreateRecipe::class)
        ->fillForm([
            'title' => $title,
            'instructions' => 'Steam it.',
            'servings' => 4,
            'ingredients' => [['name' => 'hilsa', 'quantity' => 4, 'unit' => 'piece']],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $studioSlug = Recipe::where('user_id', $viaStudio->id)->value('slug');

    /**
     * Not equal — the slug column is unique, so the second one cannot be the
     * first. What has to match is the rule: same base, and the suffix the
     * shared method hands out next.
     */
    expect($apiSlug)->toBe('ilish-bhapa-with-mustard')
        ->and($studioSlug)->toBe('ilish-bhapa-with-mustard-2')
        ->and($studioSlug)->toBe(Recipe::uniqueSlugFor($title, Recipe::where('user_id', $viaStudio->id)->value('id')));
});

test('renaming through the API uses the shared rule', function () {
    $author = User::factory()->creator()->create();
    $recipe = Recipe::factory()->for($author)->create([
        'moderation_status' => ModerationStatus::Approved,
        'slug' => 'something-else',
    ]);

    Recipe::factory()->create(['slug' => 'kosha-mangsho']);

    $this->actingAs($author)
        ->putJson("/api/recipes/{$recipe->id}", ['title' => 'Kosha Mangsho'])
        ->assertOk();

    /** The old rule appended the recipe id here; the shared one counts up. */
    expect($recipe->fresh()->slug)->toBe('kosha-mangsho-2');
});
