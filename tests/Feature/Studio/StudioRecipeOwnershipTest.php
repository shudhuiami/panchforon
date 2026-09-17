<?php

use App\Enums\ModerationStatus;
use App\Filament\Studio\Resources\Recipes\Pages\ListRecipes;
use App\Filament\Studio\Resources\Recipes\RecipeResource;
use App\Models\Recipe;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * The studio's promise is that it only ever shows you your own work. This is
 * the file that holds it, and it is written before the studio can write
 * anything, so the scoping is proven while there is still nothing to scope.
 *
 * The admin panel is the default one, so every test here has to say which
 * panel it means before a studio page will resolve its own routes.
 */
beforeEach(function () {
    Filament::setCurrentPanel('studio');
});

test('a creator sees their own recipes and not another creator\'s', function () {
    $creatorA = User::factory()->creator()->create();
    $creatorB = User::factory()->creator()->create();

    $mine = Recipe::factory()->count(2)->for($creatorA)->create();
    $theirs = Recipe::factory()->count(2)->for($creatorB)->create();
    $imported = Recipe::factory()->fromApi()->create();

    $this->actingAs($creatorA);

    Livewire::test(ListRecipes::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords($mine)
        ->assertCanNotSeeTableRecords($theirs)
        ->assertCanNotSeeTableRecords([$imported]);
});

test('every status of a creator\'s own work is on the list, and the tabs slice it', function () {
    $creator = User::factory()->creator()->create();
    $other = User::factory()->creator()->create();

    $draft = Recipe::factory()->for($creator)->create(['moderation_status' => ModerationStatus::Draft]);
    $live = Recipe::factory()->for($creator)->create(['moderation_status' => ModerationStatus::Approved]);
    $takenDown = Recipe::factory()->for($creator)->unpublished()->create();
    $waiting = Recipe::factory()->for($creator)->awaitingModeration()->create();

    /** A draft belonging to someone else must not appear on any tab. */
    $foreignDraft = Recipe::factory()->for($other)->create(['moderation_status' => ModerationStatus::Draft]);

    $this->actingAs($creator);

    Livewire::test(ListRecipes::class)
        ->assertCanSeeTableRecords([$draft, $live, $takenDown, $waiting])
        ->assertCanNotSeeTableRecords([$foreignDraft]);

    Livewire::test(ListRecipes::class, ['activeTab' => 'drafts'])
        ->assertCanSeeTableRecords([$draft])
        ->assertCanNotSeeTableRecords([$live, $takenDown, $waiting, $foreignDraft]);

    Livewire::test(ListRecipes::class, ['activeTab' => 'live'])
        ->assertCanSeeTableRecords([$live])
        ->assertCanNotSeeTableRecords([$draft, $takenDown, $foreignDraft]);

    Livewire::test(ListRecipes::class, ['activeTab' => 'unpublished'])
        ->assertCanSeeTableRecords([$takenDown])
        ->assertCanNotSeeTableRecords([$draft, $live, $foreignDraft]);
});

test('another creator\'s recipe id is not found rather than refused', function () {
    $creatorA = User::factory()->creator()->create();
    $creatorB = User::factory()->creator()->create();

    $mine = Recipe::factory()->for($creatorA)->create();
    $theirs = Recipe::factory()->for($creatorB)->create();

    /**
     * Both halves matter. The 200 proves the record route exists and that a
     * creator can reach their own recipe through it, so the 404 on the next
     * line is the scoping refusing to resolve a foreign id — not a missing
     * route answering the same way for everybody. A 403 would confirm that
     * recipe exists, which is the one thing an id-guesser wants to know.
     */
    $this->actingAs($creatorA)
        ->get("/studio/recipes/{$mine->getKey()}")
        ->assertSuccessful();

    $this->actingAs($creatorA)
        ->get("/studio/recipes/{$theirs->getKey()}")
        ->assertNotFound();
});

test('the scoping governs route-model binding, not just the table', function () {
    $creatorA = User::factory()->creator()->create();
    $creatorB = User::factory()->creator()->create();

    $mine = Recipe::factory()->for($creatorA)->create();
    $theirs = Recipe::factory()->for($creatorB)->create();

    $this->actingAs($creatorA);

    expect(RecipeResource::resolveRecordRouteBinding($mine->getKey()))->not->toBeNull()
        ->and(RecipeResource::resolveRecordRouteBinding($theirs->getKey()))->toBeNull();
});

test('the owner id is read per request, never remembered between them', function () {
    $creatorA = User::factory()->creator()->create();
    $creatorB = User::factory()->creator()->create();

    $mine = Recipe::factory()->for($creatorA)->create();
    $theirs = Recipe::factory()->for($creatorB)->create();

    $this->actingAs($creatorA);
    expect(RecipeResource::getEloquentQuery()->pluck('recipes.id')->all())->toBe([$mine->getKey()]);

    /** Same static class, different person: the second must not inherit the first's rows. */
    $this->actingAs($creatorB);
    expect(RecipeResource::getEloquentQuery()->pluck('recipes.id')->all())->toBe([$theirs->getKey()]);
});

test('a creator cannot moderate their own recipe', function () {
    $creator = User::factory()->creator()->create();
    $ownRecipe = Recipe::factory()->for($creator)->create();

    /**
     * Publishing your own work is a moderator's decision, and creators bypass
     * the queue by role rather than by gaining this ability.
     *
     * Writing used to be refused here too, while the studio was read-only.
     * Now that it is not, the write permissions are asserted in
     * StudioRecipeWritingTest, ownership and all — and moderation is what
     * stays out of a creator's reach.
     */
    expect(Gate::forUser($creator)->allows('moderate', $ownRecipe))->toBeFalse()
        ->and(Gate::forUser($creator)->allows('moderateAny', Recipe::class))->toBeFalse()
        /** A per-resource shortcut with no record to scope to, so still admin-only. */
        ->and(Gate::forUser($creator)->allows('deleteAny', Recipe::class))->toBeFalse();
});

test('a creator may write and change their own recipe, and only their own', function () {
    $creator = User::factory()->creator()->create();
    $other = User::factory()->creator()->create();

    $ownRecipe = Recipe::factory()->for($creator)->create();
    $foreignRecipe = Recipe::factory()->for($other)->create();

    expect(Gate::forUser($creator)->allows('create', Recipe::class))->toBeTrue()
        ->and(Gate::forUser($creator)->allows('update', $ownRecipe))->toBeTrue()
        ->and(Gate::forUser($creator)->allows('delete', $ownRecipe))->toBeTrue()
        ->and(Gate::forUser($creator)->allows('update', $foreignRecipe))->toBeFalse()
        ->and(Gate::forUser($creator)->allows('delete', $foreignRecipe))->toBeFalse();
});

test('a creator may view their own recipe but not another creator\'s', function () {
    $creatorA = User::factory()->creator()->create();
    $creatorB = User::factory()->creator()->create();

    $mine = Recipe::factory()->for($creatorA)->create();
    $theirs = Recipe::factory()->for($creatorB)->create();
    $imported = Recipe::factory()->fromApi()->create();

    expect(Gate::forUser($creatorA)->allows('viewAny', Recipe::class))->toBeTrue()
        ->and(Gate::forUser($creatorA)->allows('view', $mine))->toBeTrue()
        ->and(Gate::forUser($creatorA)->allows('view', $theirs))->toBeFalse()
        ->and(Gate::forUser($creatorA)->allows('view', $imported))->toBeFalse();
});

test('a member is refused the list component and gets no recipe data with the refusal', function () {
    $member = User::factory()->create();
    $creator = User::factory()->creator()->create();

    $secret = Recipe::factory()->for($creator)->create(['title' => 'Kosha Mangsho From A Locked Drawer']);

    $this->actingAs($member);

    Livewire::test(ListRecipes::class)->assertForbidden();

    /** The refusal must not carry the thing it is refusing. */
    $response = $this->get('/studio/recipes');

    $response->assertForbidden();
    $response->assertDontSee($secret->title, escape: false);

    expect(Gate::forUser($member)->allows('viewAny', Recipe::class))->toBeFalse()
        ->and(Gate::forUser($member)->allows('view', $secret))->toBeFalse();
});

test('a suspended creator is refused the list', function () {
    $suspended = User::factory()->creator()->suspended()->create();
    $own = Recipe::factory()->for($suspended)->create();

    $this->actingAs($suspended);

    /**
     * Suspension is checked in two places on the way here: canAccessPanel()
     * shuts the panel, and the policy shuts the resource behind it.
     */
    $this->get('/studio/recipes')->assertForbidden();

    expect(Gate::forUser($suspended)->allows('viewAny', Recipe::class))->toBeFalse()
        ->and(Gate::forUser($suspended)->allows('view', $own))->toBeFalse();
});

test('an admin in the studio sees their own recipes, not the whole catalogue', function () {
    $admin = User::factory()->admin()->create();
    $creator = User::factory()->creator()->create();

    $adminsOwn = Recipe::factory()->count(2)->for($admin)->create();
    $someoneElses = Recipe::factory()->count(3)->for($creator)->create();
    $imported = Recipe::factory()->fromApi()->create();

    $this->actingAs($admin);

    /**
     * The studio is about your own work whoever you are. An admin who wants
     * the catalogue has the admin panel for it.
     */
    Livewire::test(ListRecipes::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords($adminsOwn)
        ->assertCanNotSeeTableRecords($someoneElses)
        ->assertCanNotSeeTableRecords([$imported]);

    $this->get("/studio/recipes/{$someoneElses->first()->getKey()}")->assertNotFound();
});
