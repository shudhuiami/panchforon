<?php

use App\Enums\FlagReason;
use App\Enums\FlagStatus;
use App\Models\ContentFlag;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('a cook can report a recipe', function () {
    $user = User::factory()->create();
    $recipe = Recipe::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson("/api/recipes/{$recipe->id}/flag", ['reason' => 'unsafe', 'note' => 'The chicken is undercooked at that time.'])
        ->assertCreated();

    $flag = ContentFlag::sole();

    expect($flag->recipe_id)->toBe($recipe->id)
        ->and($flag->user_id)->toBe($user->id)
        ->and($flag->reason)->toBe(FlagReason::Unsafe)
        ->and($flag->status)->toBe(FlagStatus::Open);
});

test('reporting the same recipe twice updates the report rather than duplicating it', function () {
    $user = User::factory()->create();
    $recipe = Recipe::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson("/api/recipes/{$recipe->id}/flag", ['reason' => 'spam'])->assertCreated();
    $this->postJson("/api/recipes/{$recipe->id}/flag", ['reason' => 'offensive', 'note' => 'Actually it is abusive.'])->assertCreated();

    expect(ContentFlag::count())->toBe(1)
        ->and(ContentFlag::sole()->reason)->toBe(FlagReason::Offensive);
});

test('a closed report does not stop someone reporting the recipe again', function () {
    $user = User::factory()->create();
    $recipe = Recipe::factory()->create();
    ContentFlag::factory()->dismissed()->create(['recipe_id' => $recipe->id, 'user_id' => $user->id]);
    Sanctum::actingAs($user);

    $this->postJson("/api/recipes/{$recipe->id}/flag", ['reason' => 'spam'])->assertCreated();

    expect(ContentFlag::count())->toBe(2)
        ->and(ContentFlag::open()->count())->toBe(1);
});

test('two cooks reporting the same recipe make two reports', function () {
    $recipe = Recipe::factory()->create();

    foreach (User::factory()->count(2)->create() as $user) {
        Sanctum::actingAs($user);
        $this->postJson("/api/recipes/{$recipe->id}/flag", ['reason' => 'spam'])->assertCreated();
    }

    expect(ContentFlag::count())->toBe(2);
});

test('a made-up reason is rejected', function () {
    Sanctum::actingAs(User::factory()->create());
    $recipe = Recipe::factory()->create();

    $this->postJson("/api/recipes/{$recipe->id}/flag", ['reason' => 'i-just-do-not-like-it'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('reason');
});

test('a recipe the catalogue hides cannot be reported', function () {
    Sanctum::actingAs(User::factory()->create());
    $hidden = Recipe::factory()->awaitingModeration()->create();

    $this->postJson("/api/recipes/{$hidden->id}/flag", ['reason' => 'spam'])->assertNotFound();
});

test('reporting requires a signed-in cook', function () {
    $recipe = Recipe::factory()->create();

    $this->postJson("/api/recipes/{$recipe->id}/flag", ['reason' => 'spam'])->assertUnauthorized();
});

test('a suspended cook cannot report', function () {
    Sanctum::actingAs(User::factory()->create(['suspended_at' => now(), 'suspension_reason' => 'Spam']));
    $recipe = Recipe::factory()->create();

    $this->postJson("/api/recipes/{$recipe->id}/flag", ['reason' => 'spam'])->assertForbidden();
});

test('the reasons endpoint lists every reason once', function () {
    $reasons = $this->getJson('/api/flag-reasons')->assertOk()->json('data');

    expect(collect($reasons)->pluck('value')->all())->toBe(array_column(FlagReason::cases(), 'value'));
});

test('resolving a report records who decided and what they decided', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $flag = ContentFlag::factory()->create();

    $flag->resolve(FlagStatus::Actioned, $admin, 'Unpublished the recipe.');

    expect($flag->fresh()->status)->toBe(FlagStatus::Actioned)
        ->and($flag->fresh()->reviewed_by)->toBe($admin->id)
        ->and($flag->fresh()->reviewed_at)->not->toBeNull()
        ->and($flag->fresh()->resolution_note)->toBe('Unpublished the recipe.');
});
