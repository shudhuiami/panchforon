<?php

use App\Models\Rating;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('a cook can change their name and email', function () {
    $user = User::factory()->create(['name' => 'Old Name', 'email' => 'old@panchforon.test']);
    Sanctum::actingAs($user);

    $this->putJson('/api/user', ['name' => 'New Name', 'email' => 'new@panchforon.test'])
        ->assertOk()
        ->assertJsonPath('data.name', 'New Name')
        ->assertJsonPath('data.email', 'new@panchforon.test');

    expect($user->fresh()->email)->toBe('new@panchforon.test');
});

test('changing the email marks it unverified again', function () {
    $user = User::factory()->create(['email' => 'old@panchforon.test', 'email_verified_at' => now()]);
    Sanctum::actingAs($user);

    $this->putJson('/api/user', ['name' => $user->name, 'email' => 'new@panchforon.test'])->assertOk();

    expect($user->fresh()->email_verified_at)->toBeNull();
});

test('keeping the same email keeps it verified', function () {
    $user = User::factory()->create(['email' => 'same@panchforon.test', 'email_verified_at' => now()]);
    Sanctum::actingAs($user);

    $this->putJson('/api/user', ['name' => 'Renamed', 'email' => 'same@panchforon.test'])->assertOk();

    expect($user->fresh()->email_verified_at)->not->toBeNull();
});

test('an email already in use is rejected', function () {
    User::factory()->create(['email' => 'taken@panchforon.test']);
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->putJson('/api/user', ['name' => 'Someone', 'email' => 'taken@panchforon.test'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('email');
});

test('changing the password requires the current one', function () {
    $user = User::factory()->create(['password' => Hash::make('correct-horse')]);
    Sanctum::actingAs($user);

    $this->putJson('/api/user/password', [
        'current_password' => 'wrong-password',
        'password' => 'a-brand-new-one',
        'password_confirmation' => 'a-brand-new-one',
    ])->assertStatus(422)->assertJsonValidationErrors('current_password');

    expect(Hash::check('correct-horse', $user->fresh()->password))->toBeTrue();
});

test('changing the password replaces it', function () {
    $user = User::factory()->create(['password' => Hash::make('correct-horse')]);
    Sanctum::actingAs($user);

    $this->putJson('/api/user/password', [
        'current_password' => 'correct-horse',
        'password' => 'a-brand-new-one',
        'password_confirmation' => 'a-brand-new-one',
    ])->assertOk();

    expect(Hash::check('a-brand-new-one', $user->fresh()->password))->toBeTrue();
});

test('changing the password signs other devices out and keeps this one in', function () {
    $user = User::factory()->create(['password' => Hash::make('correct-horse')]);
    $user->createToken('phone');
    $user->createToken('laptop');
    Sanctum::actingAs($user);

    $token = $this->putJson('/api/user/password', [
        'current_password' => 'correct-horse',
        'password' => 'a-brand-new-one',
        'password_confirmation' => 'a-brand-new-one',
    ])->assertOk()->json('token');

    expect($token)->toBeString()
        ->and($user->tokens()->count())->toBe(1);

    $this->withToken($token)->getJson('/api/user')->assertOk();
});

test('my recipes includes the ones still awaiting moderation', function () {
    $user = User::factory()->create();
    $approved = Recipe::factory()->create(['user_id' => $user->id]);
    $pending = Recipe::factory()->awaitingModeration()->create(['user_id' => $user->id]);
    Recipe::factory()->create();
    Sanctum::actingAs($user);

    $json = $this->getJson('/api/my/recipes')->assertOk()->json('data');

    expect(collect($json)->pluck('id')->all())->toEqualCanonicalizing([$approved->id, $pending->id]);
});

test('my ratings returns each score with its dish', function () {
    $user = User::factory()->create();
    $recipe = Recipe::factory()->create();
    Rating::factory()->create(['user_id' => $user->id, 'recipe_id' => $recipe->id, 'stars' => 4]);
    Rating::factory()->create(['recipe_id' => $recipe->id]);
    Sanctum::actingAs($user);

    $json = $this->getJson('/api/my/ratings')->assertOk()->json('data');

    expect($json)->toHaveCount(1)
        ->and($json[0]['stars'])->toBe(4)
        ->and($json[0]['recipe']['id'])->toBe($recipe->id);
});

test('the account endpoints need a signed-in cook', function () {
    $this->putJson('/api/user', ['name' => 'x', 'email' => 'x@y.test'])->assertUnauthorized();
    $this->getJson('/api/my/recipes')->assertUnauthorized();
    $this->getJson('/api/my/ratings')->assertUnauthorized();
});
