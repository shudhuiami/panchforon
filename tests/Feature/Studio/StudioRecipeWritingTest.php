<?php

use App\Enums\ModerationStatus;
use App\Enums\RecipeSource;
use App\Filament\Studio\Resources\Recipes\Pages\CreateRecipe;
use App\Filament\Studio\Resources\Recipes\Pages\EditRecipe;
use App\Filament\Studio\Resources\Recipes\Pages\ListRecipes;
use App\Filament\Studio\Resources\Recipes\RecipeResource;
use App\Models\Ingredient;
use App\Models\IngredientAlias;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * Writing in the Creator Studio.
 *
 * StudioRecipeOwnershipTest holds the promise that you only ever see your own
 * work; this file holds the one that comes with being able to change it — that
 * what you save is stamped as yours, goes live under the rule the API already
 * uses, and that someone else's recipe is no more editable than it was
 * readable.
 *
 * The admin panel is the default one, so every test says which panel it means
 * before a studio page will resolve its own routes.
 */
beforeEach(function () {
    Filament::setCurrentPanel('studio');
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function studioRecipeForm(array $overrides = []): array
{
    return [
        'title' => 'Shorshe Ilish',
        'instructions' => 'Grind the mustard. Steam the fish in it.',
        'servings' => 4,
        'cuisine' => 'Bangladeshi',
        'category' => 'Fish',
        'ingredients' => [
            ['name' => 'hilsa', 'quantity' => 4, 'unit' => 'piece'],
        ],
        ...$overrides,
    ];
}

test('creating through the studio stamps owner, source, slug and status', function () {
    $creator = User::factory()->creator()->create();
    $this->actingAs($creator);

    Livewire::test(CreateRecipe::class)
        ->fillForm(studioRecipeForm())
        ->call('create')
        ->assertHasNoFormErrors();

    $recipe = Recipe::firstWhere('title', 'Shorshe Ilish');

    expect($recipe)->not->toBeNull()
        ->and($recipe->user_id)->toBe($creator->id)
        ->and($recipe->source)->toBe(RecipeSource::User)
        ->and($recipe->slug)->toBe('shorshe-ilish')
        ->and($recipe->moderation_status)->toBe(ModerationStatus::Approved)
        /** An auto-publish is nobody's decision, so nobody is recorded making it. */
        ->and($recipe->moderated_at)->toBeNull()
        ->and($recipe->moderated_by)->toBeNull();
});

test('the owner is taken from the session, not from anything the form carries', function () {
    $creator = User::factory()->creator()->create();
    $someoneElse = User::factory()->creator()->create();

    $this->actingAs($creator);

    Livewire::test(CreateRecipe::class)
        ->fillForm(studioRecipeForm(['user_id' => $someoneElse->id]))
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Recipe::firstWhere('title', 'Shorshe Ilish')->user_id)->toBe($creator->id);
});

test('a creator\'s new recipe is live immediately and shows up on the public list', function () {
    $creator = User::factory()->creator()->create();
    $this->actingAs($creator);

    Livewire::test(CreateRecipe::class)
        ->fillForm(studioRecipeForm())
        ->call('create')
        ->assertHasNoFormErrors();

    $recipe = Recipe::firstWhere('title', 'Shorshe Ilish');

    expect($recipe->moderation_status)->toBe(ModerationStatus::Approved);

    /** Signed out, so this is the public catalogue and not the author's view of it. */
    auth()->logout();

    $this->getJson('/api/recipes')
        ->assertOk()
        ->assertJsonFragment(['slug' => 'shorshe-ilish']);

    $this->getJson('/api/recipes/shorshe-ilish')->assertOk();
});

test('keeping it a draft keeps it private', function () {
    $creator = User::factory()->creator()->create();
    $this->actingAs($creator);

    Livewire::test(CreateRecipe::class)
        ->fillForm(studioRecipeForm(['keep_as_draft' => true]))
        ->call('create')
        ->assertHasNoFormErrors();

    $recipe = Recipe::firstWhere('title', 'Shorshe Ilish');

    expect($recipe->moderation_status)->toBe(ModerationStatus::Draft);

    auth()->logout();

    $this->getJson('/api/recipes')
        ->assertOk()
        ->assertJsonMissing(['slug' => 'shorshe-ilish']);

    $this->getJson('/api/recipes/shorshe-ilish')->assertNotFound();

    /** Its author still reaches it; that is what makes it a draft and not a hole. */
    $this->actingAs($creator)->getJson('/api/recipes/shorshe-ilish')->assertOk();
});

test('a creator who cannot skip review is queued rather than published', function () {
    /**
     * The same rule ModerationStatus::forAuthor() applies over the API: a
     * suspended account may not publish without review. It cannot reach the
     * studio at all, so this asserts the rule itself rather than the screen.
     */
    $suspended = User::factory()->creator()->suspended()->create();

    expect(ModerationStatus::forAuthor($suspended, savesAsDraft: false))->toBe(ModerationStatus::Pending)
        ->and(ModerationStatus::forAuthor($suspended, savesAsDraft: true))->toBe(ModerationStatus::Draft)
        ->and(ModerationStatus::forAuthor(null, savesAsDraft: false))->toBe(ModerationStatus::Pending);
});

test('a suspended creator cannot create', function () {
    $suspended = User::factory()->creator()->suspended()->create();

    $this->actingAs($suspended);

    expect(Gate::forUser($suspended)->allows('create', Recipe::class))->toBeFalse()
        ->and(RecipeResource::canCreate())->toBeFalse();

    Livewire::test(CreateRecipe::class)->assertForbidden();

    /** And the panel itself is shut, which is the gate before the policy. */
    $this->get('/studio/recipes/create')->assertForbidden();

    expect(Recipe::count())->toBe(0);
});

test('a member who is not a creator cannot create', function () {
    $member = User::factory()->create();

    $this->actingAs($member);

    expect(Gate::forUser($member)->allows('create', Recipe::class))->toBeFalse();

    Livewire::test(CreateRecipe::class)->assertForbidden();
    $this->get('/studio/recipes/create')->assertForbidden();
});

test('editing someone else\'s recipe is impossible', function () {
    $creatorA = User::factory()->creator()->create();
    $creatorB = User::factory()->creator()->create();

    $mine = Recipe::factory()->for($creatorA)->create();
    $theirs = Recipe::factory()->for($creatorB)->create(['title' => 'Not Yours']);

    $this->actingAs($creatorA);

    /**
     * Both halves again: the 200 proves the edit route exists and resolves for
     * its owner, so the 404 is the scoping refusing a foreign id rather than a
     * missing route answering the same way for everyone.
     */
    $this->get("/studio/recipes/{$mine->getKey()}/edit")->assertSuccessful();

    $response = $this->get("/studio/recipes/{$theirs->getKey()}/edit");
    $response->assertNotFound();
    $response->assertDontSee($theirs->title, escape: false);

    /**
     * The component refuses the same id on its own, not just behind the route.
     * It raises rather than returns: the scoped query finds no such record, so
     * route-model binding has nothing to bind and the 404 above is what the
     * HTTP handler makes of this.
     */
    expect(fn () => Livewire::test(EditRecipe::class, ['record' => $theirs->getKey()]))
        ->toThrow(ModelNotFoundException::class);

    expect(Gate::forUser($creatorA)->allows('update', $theirs))->toBeFalse()
        ->and(Gate::forUser($creatorA)->allows('delete', $theirs))->toBeFalse()
        ->and($theirs->fresh()->title)->toBe('Not Yours');
});

test('a creator may edit their own recipe without it dropping off the site', function () {
    $creator = User::factory()->creator()->create();
    $recipe = Recipe::factory()->for($creator)->create([
        'title' => 'Old Title',
        'slug' => 'old-title',
        'moderation_status' => ModerationStatus::Approved,
    ]);

    /** The repeater insists on at least one row, so a recipe has to have one. */
    RecipeIngredient::factory()->for($recipe)->create(['raw_text' => '1 tsp turmeric', 'position' => 0]);

    $this->actingAs($creator);

    Livewire::test(EditRecipe::class, ['record' => $recipe->getKey()])
        ->fillForm([
            'title' => 'New Title',
            'instructions' => 'Changed.',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $recipe->refresh();

    expect($recipe->title)->toBe('New Title')
        ->and($recipe->instructions)->toBe('Changed.')
        /** An edit is a change of content. It never moves the moderation state. */
        ->and($recipe->moderation_status)->toBe(ModerationStatus::Approved)
        /** The studio form has no slug field, so the URL a reader bookmarked holds. */
        ->and($recipe->slug)->toBe('old-title');
});

test('ingredients round-trip through the repeater, optional flag and note included', function () {
    $creator = User::factory()->creator()->create();
    $this->actingAs($creator);

    Livewire::test(CreateRecipe::class)
        ->fillForm(studioRecipeForm([
            'ingredients' => [
                ['name' => 'hilsa', 'quantity' => 4, 'unit' => 'piece'],
                ['name' => 'mustard oil', 'quantity' => 2, 'unit' => 'tbsp', 'note' => 'plus a little to finish'],
                ['name' => 'green chilli', 'quantity' => 6, 'unit' => 'piece', 'is_optional' => true],
            ],
        ]))
        ->call('create')
        ->assertHasNoFormErrors();

    $recipe = Recipe::firstWhere('title', 'Shorshe Ilish');
    $rows = $recipe->ingredients()->with('ingredient')->get();

    expect($rows)->toHaveCount(3)
        /** ingredients() orders by position, so this is the order they were typed. */
        ->and($rows->pluck('ingredient.canonical_name')->all())
        ->toBe(['hilsa', 'mustard oil', 'green chilli'])
        ->and($rows->pluck('position')->all())->toBe([0, 1, 2])
        ->and($rows[0]->quantity)->toBe(4.0)
        ->and($rows[0]->unit)->toBe('piece')
        ->and($rows[0]->is_optional)->toBeFalse()
        ->and($rows[1]->note)->toBe('plus a little to finish')
        ->and($rows[1]->is_optional)->toBeFalse()
        ->and($rows[2]->is_optional)->toBeTrue()
        /** raw_text is the line as a reader will see it, rebuilt from the boxes. */
        ->and($rows[1]->raw_text)->toBe('2 tbsp mustard oil');

    /** The view screen the author lands on after saving shows the list too. */
    $this->get("/studio/recipes/{$recipe->getKey()}")
        ->assertSuccessful()
        ->assertSee('2 tbsp mustard oil', escape: false)
        ->assertSee('plus a little to finish', escape: false);

    /** And back out again: the edit form must show what was saved. */
    Livewire::test(EditRecipe::class, ['record' => $recipe->getKey()])
        ->assertSuccessful()
        ->assertFormSet(function (array $state): array {
            $filled = array_values($state['ingredients']);

            /**
             * The name box holds no column of its own, so this is the proof
             * that it is read back off the canonical ingredient each row was
             * resolved to rather than lost on the way out.
             */
            expect($filled)->toHaveCount(3)
                ->and(array_column($filled, 'name'))->toBe(['hilsa', 'mustard oil', 'green chilli'])
                ->and($filled[0]['is_optional'])->toBeFalsy()
                ->and($filled[2]['is_optional'])->toBeTruthy()
                ->and($filled[1]['note'])->toBe('plus a little to finish')
                ->and((float) $filled[1]['quantity'])->toBe(2.0)
                ->and($filled[1]['unit'])->toBe('tbsp');

            return [];
        });
});

test('a repeater row resolves to a canonical ingredient before it creates one', function () {
    $cilantro = Ingredient::factory()->create(['canonical_name' => 'cilantro']);
    IngredientAlias::create(['ingredient_id' => $cilantro->id, 'alias' => 'coriander leaf']);

    $creator = User::factory()->creator()->create();
    $this->actingAs($creator);

    Livewire::test(CreateRecipe::class)
        ->fillForm(studioRecipeForm([
            'ingredients' => [
                /** Matches the canonical name outright. */
                ['name' => 'Cilantro', 'quantity' => 1, 'unit' => 'cup'],
                /** Reaches the same ingredient through the alias table. */
                ['name' => 'coriander leaves', 'quantity' => 2, 'unit' => 'tbsp'],
                /** Matches nothing, so a canonical ingredient is created last. */
                ['name' => 'panch phoron', 'quantity' => 1, 'unit' => 'tsp'],
            ],
        ]))
        ->call('create')
        ->assertHasNoFormErrors();

    $rows = Recipe::firstWhere('title', 'Shorshe Ilish')->ingredients()->get();

    expect($rows[0]->ingredient_id)->toBe($cilantro->id)
        ->and($rows[1]->ingredient_id)->toBe($cilantro->id)
        ->and($rows[2]->ingredient_id)->not->toBe($cilantro->id)
        ->and(Ingredient::whereKey($rows[2]->ingredient_id)->value('canonical_name'))->toBe('panch phoron');
});

test('editing ingredients replaces the list rather than appending to it', function () {
    $creator = User::factory()->creator()->create();
    $this->actingAs($creator);

    Livewire::test(CreateRecipe::class)
        ->fillForm(studioRecipeForm([
            'ingredients' => [
                ['name' => 'hilsa', 'quantity' => 4, 'unit' => 'piece'],
                ['name' => 'turmeric', 'quantity' => 1, 'unit' => 'tsp'],
            ],
        ]))
        ->call('create')
        ->assertHasNoFormErrors();

    $recipe = Recipe::firstWhere('title', 'Shorshe Ilish');

    Livewire::test(EditRecipe::class, ['record' => $recipe->getKey()])
        ->fillForm([
            'ingredients' => [
                ['name' => 'hilsa', 'quantity' => 6, 'unit' => 'piece'],
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $rows = $recipe->ingredients()->get();

    expect($rows)->toHaveCount(1)
        ->and($rows[0]->quantity)->toBe(6.0);
});

test('a recipe needs at least one ingredient', function () {
    $creator = User::factory()->creator()->create();
    $this->actingAs($creator);

    Livewire::test(CreateRecipe::class)
        ->fillForm(studioRecipeForm(['ingredients' => []]))
        ->call('create')
        ->assertHasFormErrors(['ingredients']);

    expect(Recipe::count())->toBe(0);
});

test('a pasted YouTube link is stored as the bare video id', function () {
    $creator = User::factory()->creator()->create();
    $this->actingAs($creator);

    Livewire::test(CreateRecipe::class)
        ->fillForm(studioRecipeForm([
            'youtube_video_id' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=42s',
        ]))
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Recipe::firstWhere('title', 'Shorshe Ilish')->youtube_video_id)->toBe('dQw4w9WgXcQ');
});

test('something that is not a YouTube link is refused, and an empty box is fine', function () {
    $creator = User::factory()->creator()->create();
    $this->actingAs($creator);

    Livewire::test(CreateRecipe::class)
        ->fillForm(studioRecipeForm(['youtube_video_id' => 'https://vimeo.com/76979871']))
        ->call('create')
        ->assertHasFormErrors(['youtube_video_id']);

    Livewire::test(CreateRecipe::class)
        ->fillForm(studioRecipeForm(['youtube_video_id' => '']))
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Recipe::firstWhere('title', 'Shorshe Ilish')->youtube_video_id)->toBeNull();
});

test('the studio form offers no author and no moderation status', function () {
    $creator = User::factory()->creator()->create();
    $this->actingAs($creator);

    Livewire::test(CreateRecipe::class)
        ->assertSuccessful()
        /**
         * The admin form shows a disabled author Select so a moderator can see
         * whose submission they are correcting. Here it could only ever say one
         * name, so there is no field at all rather than a disabled one — and a
         * field that is not on the form is a field a request cannot carry.
         */
        ->assertFormFieldDoesNotExist('user_id')
        /** Status is changed by actions that record who acted, never typed. */
        ->assertFormFieldDoesNotExist('moderation_status')
        ->assertFormFieldDoesNotExist('slug')
        ->assertFormFieldExists('title')
        ->assertFormFieldExists('instructions')
        ->assertFormFieldExists('youtube_video_id')
        ->assertFormFieldExists('ingredients')
        ->assertFormFieldExists('keep_as_draft');
});

test('the draft question is asked when writing and not when editing', function () {
    $creator = User::factory()->creator()->create();
    $recipe = Recipe::factory()->for($creator)->create();
    RecipeIngredient::factory()->for($recipe)->create(['position' => 0]);

    $this->actingAs($creator);

    /**
     * Once a recipe exists, moving it between draft and live is a change of
     * state rather than of content, so the content form stops asking.
     */
    Livewire::test(EditRecipe::class, ['record' => $recipe->getKey()])
        ->assertSuccessful()
        ->assertFormFieldDoesNotExist('keep_as_draft');
});

test('the studio table offers no bulk delete', function () {
    $creator = User::factory()->creator()->create();
    Recipe::factory()->for($creator)->create();

    $this->actingAs($creator);

    /**
     * RecipePolicy::deleteAny() is asked with no record, so it cannot be
     * scoped to a creator's own rows — which is why it stays admin-only and
     * why the studio table carries no bulk actions at all.
     */
    expect(Gate::forUser($creator)->allows('deleteAny', Recipe::class))->toBeFalse();

    $table = Livewire::test(ListRecipes::class)->instance()->getTable();

    expect($table->getToolbarActions())->toBeEmpty()
        ->and($table->getFlatBulkActions())->toBeEmpty();
});

test('a creator may delete their own recipe and nobody else\'s', function () {
    $creatorA = User::factory()->creator()->create();
    $creatorB = User::factory()->creator()->create();

    $mine = Recipe::factory()->for($creatorA)->create();
    $theirs = Recipe::factory()->for($creatorB)->create();
    $imported = Recipe::factory()->fromApi()->create();

    expect(Gate::forUser($creatorA)->allows('delete', $mine))->toBeTrue()
        ->and(Gate::forUser($creatorA)->allows('delete', $theirs))->toBeFalse()
        /** An imported recipe has a null author and belongs to nobody. */
        ->and(Gate::forUser($creatorA)->allows('delete', $imported))->toBeFalse();
});
