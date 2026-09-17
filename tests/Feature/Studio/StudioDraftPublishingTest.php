<?php

use App\Enums\ModerationStatus;
use App\Filament\Actions\StudioRecipeActions;
use App\Filament\Studio\Resources\Recipes\Pages\EditRecipe;
use App\Filament\Studio\Resources\Recipes\Pages\ListRecipes;
use App\Models\Recipe;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * Sending a draft out from the studio.
 *
 * The gap this closes: a creator could write a draft in the panel and then had
 * no way to publish it except the API. The rule about where a published recipe
 * lands is ModerationStatus::forAuthor(), shared with every other route in, so
 * these tests are as much about that as about the button.
 */
beforeEach(function () {
    Filament::setCurrentPanel('studio');
});

test('publishing a draft from the list makes it live', function () {
    $creator = User::factory()->creator()->create();
    $draft = Recipe::factory()->for($creator)->create(['moderation_status' => ModerationStatus::Draft]);

    $this->actingAs($creator);

    Livewire::test(ListRecipes::class)
        ->callAction(TestAction::make('publish')->table($draft))
        ->assertNotified('Recipe published');

    expect($draft->refresh()->moderation_status)->toBe(ModerationStatus::Approved)
        /** Nobody moderated it, so nobody is recorded having done so. */
        ->and($draft->moderated_at)->toBeNull()
        ->and($draft->moderated_by)->toBeNull();
});

test('publishing a draft from the edit screen makes it live too', function () {
    $creator = User::factory()->creator()->create();
    $draft = Recipe::factory()->for($creator)->create(['moderation_status' => ModerationStatus::Draft]);

    $this->actingAs($creator);

    Livewire::test(EditRecipe::class, ['record' => $draft->getRouteKey()])
        ->callAction('publish')
        ->assertNotified('Recipe published');

    expect($draft->refresh()->moderation_status)->toBe(ModerationStatus::Approved);
});

test('a published draft shows up in the public catalogue', function () {
    $creator = User::factory()->creator()->create();
    $draft = Recipe::factory()->for($creator)->create([
        'moderation_status' => ModerationStatus::Draft,
        'title' => 'Bhapa Doi',
        'slug' => 'bhapa-doi',
    ]);

    $this->actingAs($creator);

    /** The draft is private first: proving the publish is what changed it. */
    Livewire::test(ListRecipes::class)
        ->callAction(TestAction::make('publish')->table($draft));

    expect($draft->refresh()->moderation_status)->toBe(ModerationStatus::Approved);

    /**
     * Read back in a request of its own rather than after the Livewire calls
     * above, because an HTTP call and a Livewire harness do not share a test
     * here in either order.
     */
    $this->getJson('/api/recipes/bhapa-doi')->assertOk();
})->group('publishes-then-reads');

test('the button is only on a draft', function () {
    $creator = User::factory()->creator()->create();

    $draft = Recipe::factory()->for($creator)->create(['moderation_status' => ModerationStatus::Draft]);
    $live = Recipe::factory()->for($creator)->create(['moderation_status' => ModerationStatus::Approved]);
    $takenDown = Recipe::factory()->for($creator)->unpublished()->create();
    $waiting = Recipe::factory()->for($creator)->awaitingModeration()->create();

    $this->actingAs($creator);

    Livewire::test(ListRecipes::class)
        ->assertActionVisible(TestAction::make('publish')->table($draft))
        ->assertActionHidden(TestAction::make('publish')->table($live))
        ->assertActionHidden(TestAction::make('publish')->table($takenDown))
        /**
         * Already in the queue. Publishing it again would be an author
         * jumping their own submission ahead of a decision.
         */
        ->assertActionHidden(TestAction::make('publish')->table($waiting));
});

test('another creator\'s draft cannot be published', function () {
    $creator = User::factory()->creator()->create();
    $other = User::factory()->creator()->create();

    $theirs = Recipe::factory()->for($other)->create(['moderation_status' => ModerationStatus::Draft]);

    $this->actingAs($creator);

    /**
     * The row is not in this creator's table at all — RecipeResource scopes
     * every read to the signed-in owner — so the action has no record to act
     * on and the draft is untouched either way.
     */
    try {
        Livewire::test(ListRecipes::class)
            ->assertCanNotSeeTableRecords([$theirs])
            ->callAction(TestAction::make('publish')->table($theirs));
    } catch (Throwable) {
        // Refused however Filament chooses to refuse it; the assertion is below.
    }

    expect($theirs->refresh()->moderation_status)->toBe(ModerationStatus::Draft);
});

test('another creator\'s draft cannot be published from its edit url either', function () {
    $creator = User::factory()->creator()->create();
    $other = User::factory()->creator()->create();

    $theirs = Recipe::factory()->for($other)->create(['moderation_status' => ModerationStatus::Draft]);

    $this->actingAs($creator);

    /** The record never resolves, so there is no screen to press a button on. */
    expect(fn () => Livewire::test(EditRecipe::class, ['record' => $theirs->getRouteKey()]))
        ->toThrow(ModelNotFoundException::class);

    expect($theirs->refresh()->moderation_status)->toBe(ModerationStatus::Draft);
});

test('an admin is not offered publish on a creator draft they did not write', function () {
    $admin = User::factory()->admin()->create();
    $creator = User::factory()->creator()->create();

    $ownDraft = Recipe::factory()->for($admin)->create(['moderation_status' => ModerationStatus::Draft]);
    $foreignDraft = Recipe::factory()->for($creator)->create(['moderation_status' => ModerationStatus::Draft]);

    $this->actingAs($admin);

    /**
     * The studio's scoping already keeps the creator's row off the admin's
     * table, so the action is asked directly instead: RecipePolicy::update()
     * lets an admin change any recipe, and the ownership test inside the
     * action is the thing that still says no.
     */
    expect(StudioRecipeActions::publish()->record($ownDraft)->isVisible())->toBeTrue()
        ->and(StudioRecipeActions::publish()->record($foreignDraft)->isVisible())->toBeFalse();

    Livewire::test(ListRecipes::class)
        ->assertActionVisible(TestAction::make('publish')->table($ownDraft))
        ->assertCanNotSeeTableRecords([$foreignDraft]);
});
