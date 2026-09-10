<?php

use App\Enums\ModerationStatus;
use App\Filament\Resources\Recipes\Pages\ListRecipes;
use App\Models\Recipe;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('an admin can approve a submission, recording who acted', function () {
    $admin = User::factory()->admin()->create();
    $recipe = Recipe::factory()->awaitingModeration()->create();

    $this->actingAs($admin);

    Livewire::test(ListRecipes::class)
        ->callAction(TestAction::make('approve')->table($recipe))
        ->assertHasNoActionErrors();

    $recipe->refresh();

    expect($recipe->moderation_status)->toBe(ModerationStatus::Approved)
        ->and($recipe->moderated_by)->toBe($admin->getKey())
        ->and($recipe->moderated_at)->not->toBeNull();

    $this->getJson('/api/recipes')->assertJsonCount(1, 'data');
});

test('an admin can unpublish a recipe, which removes it from the public list', function () {
    $admin = User::factory()->admin()->create();
    $recipe = Recipe::factory()->create();

    $this->getJson('/api/recipes')->assertJsonCount(1, 'data');

    $this->actingAs($admin);

    Livewire::test(ListRecipes::class)
        ->callAction(TestAction::make('unpublish')->table($recipe))
        ->assertHasNoActionErrors();

    expect($recipe->fresh()->moderation_status)->toBe(ModerationStatus::Unpublished);

    $this->getJson('/api/recipes')->assertJsonCount(0, 'data');
});

test('imported recipes cannot be moderated', function () {
    $admin = User::factory()->admin()->create();
    $imported = Recipe::factory()->fromApi()->create();

    $this->actingAs($admin);

    Livewire::test(ListRecipes::class)
        ->assertActionHidden(TestAction::make('approve')->table($imported))
        ->assertActionHidden(TestAction::make('unpublish')->table($imported));

    expect(Gate::forUser($admin)->allows('moderate', $imported))->toBeFalse();
});

test('bulk approval publishes submissions and skips imports', function () {
    $admin = User::factory()->admin()->create();
    $pending = Recipe::factory()->count(3)->awaitingModeration()->create();
    $imported = Recipe::factory()->fromApi()->create();

    $this->actingAs($admin);

    Livewire::test(ListRecipes::class)
        ->callTableBulkAction('approveSelected', $pending->push($imported))
        ->assertHasNoActionErrors();

    expect(Recipe::query()->awaitingModeration()->count())->toBe(0)
        ->and(Recipe::query()->where('moderation_status', ModerationStatus::Approved)->count())->toBe(4)
        ->and($imported->fresh()->moderated_by)->toBeNull();
});

test('bulk unpublish hides submissions from the public list', function () {
    $admin = User::factory()->admin()->create();
    $recipes = Recipe::factory()->count(3)->create();

    $this->getJson('/api/recipes')->assertJsonCount(3, 'data');

    $this->actingAs($admin);

    Livewire::test(ListRecipes::class)
        ->callTableBulkAction('unpublishSelected', $recipes)
        ->assertHasNoActionErrors();

    $this->getJson('/api/recipes')->assertJsonCount(0, 'data');
});

test('a non-admin is refused the recipe list component and leaks no data', function () {
    $member = User::factory()->create();
    Recipe::factory()->awaitingModeration()->create(['title' => 'Zzyzx Secret Recipe']);

    $this->actingAs($member);

    $component = Livewire::test(ListRecipes::class)->assertForbidden();

    expect($component->html())->not->toContain('Zzyzx Secret Recipe');
});

test('a non-admin calling a moderation action changes nothing', function () {
    $member = User::factory()->create();
    $recipe = Recipe::factory()->awaitingModeration()->create();

    $this->actingAs($member);

    rescue(fn () => Livewire::test(ListRecipes::class)
        ->callAction(TestAction::make('approve')->table($recipe)), report: false);

    expect($recipe->fresh()->moderation_status)->toBe(ModerationStatus::Pending);
});

test('a suspended admin cannot moderate', function () {
    $suspended = User::factory()->admin()->suspended()->create();
    $recipe = Recipe::factory()->awaitingModeration()->create();

    $this->actingAs($suspended);

    Livewire::test(ListRecipes::class)->assertForbidden();

    rescue(fn () => Livewire::test(ListRecipes::class)
        ->callAction(TestAction::make('approve')->table($recipe)), report: false);

    expect($recipe->fresh()->moderation_status)->toBe(ModerationStatus::Pending)
        ->and(Gate::forUser($suspended)->allows('moderate', $recipe))->toBeFalse();
});
