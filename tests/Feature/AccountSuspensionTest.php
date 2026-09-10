<?php

use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a suspended user cannot log in', function () {
    $user = User::factory()->suspended()->create([
        'email' => 'banned@example.com',
        'password' => 'password',
    ]);

    $response = $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('email');

    expect($response->json('errors.email.0'))->toBe('This account has been suspended.');
});

test('a suspended account reveals nothing extra when the password is wrong', function () {
    $user = User::factory()->suspended()->create(['password' => 'password']);

    $response = $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(422);

    expect($response->json('errors.email.0'))
        ->toBe('The provided credentials do not match our records.');
});

test('a token issued before suspension stops working', function () {
    $user = User::factory()->create();
    $token = $user->createToken('auth-token')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/user')
        ->assertStatus(200);

    $user->forceFill(['suspended_at' => now(), 'suspension_reason' => 'Spam'])->save();

    /**
     * Guards memoize the resolved user for the lifetime of the application
     * instance, which a test reuses across requests. Production resolves the
     * guard from scratch on every request, so forget it here to match.
     */
    $this->app['auth']->forgetGuards();

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/user')
        ->assertStatus(403)
        ->assertJsonPath('message', 'Your account has been suspended.')
        ->assertJsonPath('reason', 'Spam');
});

test('a suspended user cannot reach any authenticated endpoint', function () {
    $user = User::factory()->suspended()->create();
    $recipe = Recipe::factory()->create();

    $this->actingAs($user)->getJson('/api/meal-plan')->assertStatus(403);
    $this->actingAs($user)->postJson('/api/recipes', [])->assertStatus(403);
    $this->actingAs($user)
        ->putJson("/api/recipes/{$recipe->id}/rating", ['stars' => 5])
        ->assertStatus(403);
});

test('lifting a suspension restores access', function () {
    $user = User::factory()->suspended()->create();

    $this->actingAs($user)->getJson('/api/user')->assertStatus(403);

    $user->forceFill(['suspended_at' => null, 'suspension_reason' => null])->save();

    $this->app['auth']->forgetGuards();

    $this->actingAs($user->fresh())->getJson('/api/user')->assertStatus(200);
});
