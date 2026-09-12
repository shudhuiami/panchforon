<?php

use App\Enums\FlagStatus;
use App\Enums\ModerationStatus;
use App\Filament\Resources\ContentFlags\ContentFlagResource;
use App\Filament\Resources\ContentFlags\Pages\ListContentFlags;
use App\Models\ContentFlag;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
});

test('an ordinary cook cannot reach the report queue', function () {
    $this->actingAs(User::factory()->create())
        ->get(ContentFlagResource::getUrl('index'))
        ->assertForbidden();
});

test('a suspended admin cannot reach the report queue', function () {
    $suspended = User::factory()->create(['is_admin' => true, 'suspended_at' => now(), 'suspension_reason' => 'Abuse']);

    $this->actingAs($suspended)
        ->get(ContentFlagResource::getUrl('index'))
        ->assertForbidden();
});

test('the queue lists open reports first', function () {
    $open = ContentFlag::factory()->create();
    $dismissed = ContentFlag::factory()->dismissed()->create();

    Livewire::actingAs($this->admin)
        ->test(ListContentFlags::class)
        ->assertCanSeeTableRecords([$open])
        ->assertCanNotSeeTableRecords([$dismissed]);
});

test('unpublishing from a report hides the recipe and closes every open report on it', function () {
    $recipe = Recipe::factory()->create();
    $first = ContentFlag::factory()->create(['recipe_id' => $recipe->id]);
    $second = ContentFlag::factory()->create(['recipe_id' => $recipe->id]);
    $elsewhere = ContentFlag::factory()->create();

    Livewire::actingAs($this->admin)
        ->test(ListContentFlags::class)
        ->callTableAction('unpublishRecipe', $first, ['resolution_note' => 'Unsafe cooking time.']);

    expect($recipe->fresh()->moderation_status)->toBe(ModerationStatus::Unpublished)
        ->and($first->fresh()->status)->toBe(FlagStatus::Actioned)
        ->and($second->fresh()->status)->toBe(FlagStatus::Actioned)
        ->and($second->fresh()->resolution_note)->toBe('Unsafe cooking time.')
        ->and($elsewhere->fresh()->status)->toBe(FlagStatus::Open);
});

test('marking a report actioned records the decision without touching the recipe', function () {
    $recipe = Recipe::factory()->create();
    $flag = ContentFlag::factory()->create(['recipe_id' => $recipe->id]);

    Livewire::actingAs($this->admin)
        ->test(ListContentFlags::class)
        ->callTableAction('markActioned', $flag, ['resolution_note' => 'Fixed the oven temperature.']);

    expect($flag->fresh()->status)->toBe(FlagStatus::Actioned)
        ->and($flag->fresh()->reviewed_by)->toBe($this->admin->id)
        ->and($recipe->fresh()->moderation_status)->toBe(ModerationStatus::Approved);
});

test('dismissing a report leaves the recipe alone', function () {
    $recipe = Recipe::factory()->create();
    $flag = ContentFlag::factory()->create(['recipe_id' => $recipe->id]);

    Livewire::actingAs($this->admin)
        ->test(ListContentFlags::class)
        ->callTableAction('dismiss', $flag, ['resolution_note' => 'Nothing wrong with it.']);

    expect($flag->fresh()->status)->toBe(FlagStatus::Dismissed)
        ->and($recipe->fresh()->moderation_status)->toBe(ModerationStatus::Approved);
});

test('a report on an imported recipe offers no unpublish action', function () {
    $imported = Recipe::factory()->fromApi()->create();
    $flag = ContentFlag::factory()->create(['recipe_id' => $imported->id]);

    Livewire::actingAs($this->admin)
        ->test(ListContentFlags::class)
        ->assertTableActionHidden('unpublishRecipe', $flag);
});

test('a closed report offers no decisions', function () {
    $flag = ContentFlag::factory()->dismissed()->create();

    Livewire::actingAs($this->admin)
        ->test(ListContentFlags::class, ['activeTab' => 'dismissed'])
        ->assertTableActionHidden('markActioned', $flag)
        ->assertTableActionHidden('dismiss', $flag);
});

test('reports can be dismissed in bulk, skipping the ones already closed', function () {
    $open = ContentFlag::factory()->count(2)->create();
    $already = ContentFlag::factory()->actioned()->create();

    Livewire::actingAs($this->admin)
        ->test(ListContentFlags::class, ['activeTab' => 'all'])
        ->callTableBulkAction('dismissSelected', $open->push($already));

    expect(ContentFlag::where('status', FlagStatus::Dismissed)->count())->toBe(2)
        ->and($already->fresh()->status)->toBe(FlagStatus::Actioned);
});

test('the sidebar badge counts only open reports', function () {
    ContentFlag::factory()->count(3)->create();
    ContentFlag::factory()->dismissed()->create();

    expect(ContentFlagResource::getNavigationBadge())->toBe('3');
});
