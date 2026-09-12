<?php

use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;

uses(RefreshDatabase::class);

/**
 * Switch Google on and answer both of its endpoints with the given profile.
 *
 * @param  array<string, mixed>  $profile
 */
function fakeGoogle(array $profile = []): void
{
    config(['services.google.client_id' => 'test-client', 'services.google.client_secret' => 'test-secret']);

    Http::fake([
        'oauth2.googleapis.com/token' => Http::response(['access_token' => 'provider-token']),
        'openidconnect.googleapis.com/v1/userinfo' => Http::response(array_merge([
            'sub' => '1234567890',
            'name' => 'Ahmed Zobayer',
            'email' => 'ahmed@panchforon.test',
            'email_verified' => true,
        ], $profile)),
    ]);
}

/** Walk the callback with a state the session already holds. */
function completeCallback(string $provider = 'google'): TestResponse
{
    return test()
        ->withSession(['social_oauth_state' => ['provider' => $provider, 'value' => 'known-state']])
        ->get("/auth/{$provider}/callback?code=auth-code&state=known-state");
}

test('an unconfigured provider does not exist', function () {
    config(['services.google.client_id' => null, 'services.google.client_secret' => null]);

    $this->get('/auth/google/redirect')->assertNotFound();
    $this->get('/auth/google/callback?code=x&state=y')->assertNotFound();
});

test('an unknown provider does not exist', function () {
    $this->get('/auth/myspace/redirect')->assertNotFound();
});

test('the settings endpoint lists only configured providers', function () {
    $this->getJson('/api/settings')->assertJsonPath('data.social_logins', []);

    config(['services.google.client_id' => 'id', 'services.google.client_secret' => 'secret']);

    $this->getJson('/api/settings')->assertJsonPath('data.social_logins', [['key' => 'google', 'label' => 'Google']]);
});

test('starting a sign-in sends the browser to the provider with a state', function () {
    config(['services.google.client_id' => 'test-client', 'services.google.client_secret' => 'test-secret']);

    $response = $this->get('/auth/google/redirect');

    $response->assertRedirectContains('accounts.google.com');
    $response->assertRedirectContains('client_id=test-client');
    expect(session('social_oauth_state.provider'))->toBe('google')
        ->and(session('social_oauth_state.value'))->toBeString();
});

test('a callback whose state does not match is refused', function () {
    fakeGoogle();

    $this->withSession(['social_oauth_state' => ['provider' => 'google', 'value' => 'the-real-state']])
        ->get('/auth/google/callback?code=auth-code&state=a-forged-state')
        ->assertRedirectContains('/login/social?error=');

    expect(User::count())->toBe(0);
});

test('a first sign-in creates the account and links the provider', function () {
    fakeGoogle();

    completeCallback()->assertRedirectContains('/login/social?code=');

    $user = User::firstWhere('email', 'ahmed@panchforon.test');

    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('Ahmed Zobayer')
        ->and($user->email_verified_at)->not->toBeNull()
        ->and(SocialAccount::where('user_id', $user->id)->where('provider', 'google')->exists())->toBeTrue();
});

test('signing in again reuses the linked account', function () {
    fakeGoogle();
    completeCallback();
    completeCallback();

    expect(User::count())->toBe(1)
        ->and(SocialAccount::count())->toBe(1);
});

test('a verified address claims a matching account', function () {
    $existing = User::factory()->create(['email' => 'ahmed@panchforon.test']);
    fakeGoogle();

    completeCallback()->assertRedirectContains('/login/social?code=');

    expect(User::count())->toBe(1)
        ->and(SocialAccount::where('user_id', $existing->id)->exists())->toBeTrue();
});

test('an unverified address may not claim someone else’s account', function () {
    User::factory()->create(['email' => 'ahmed@panchforon.test']);
    fakeGoogle(['email_verified' => false]);

    completeCallback()->assertRedirectContains('error=');

    expect(SocialAccount::count())->toBe(0);
});

test('a provider that shares no address cannot create an account', function () {
    fakeGoogle(['email' => null]);

    completeCallback()->assertRedirectContains('error=');

    expect(User::count())->toBe(0);
});

test('a suspended cook cannot sign in with a provider', function () {
    $user = User::factory()->create(['email' => 'ahmed@panchforon.test', 'suspended_at' => now(), 'suspension_reason' => 'Spam']);
    SocialAccount::factory()->create(['user_id' => $user->id, 'provider' => 'google', 'provider_id' => '1234567890']);
    fakeGoogle();

    completeCallback()->assertRedirectContains('error=');
});

test('the one-time code becomes a token exactly once', function () {
    fakeGoogle();
    $redirect = completeCallback()->headers->get('Location');
    parse_str((string) parse_url((string) $redirect, PHP_URL_QUERY), $query);

    $this->postJson('/api/auth/social/exchange', ['code' => $query['code']])
        ->assertOk()
        ->assertJsonPath('user.email', 'ahmed@panchforon.test')
        ->assertJsonStructure(['user', 'token']);

    $this->postJson('/api/auth/social/exchange', ['code' => $query['code']])->assertStatus(422);
});

test('a made-up exchange code is refused', function () {
    $this->postJson('/api/auth/social/exchange', ['code' => str_repeat('a', 64)])->assertStatus(422);
});

test('a provider that rejects the code fails cleanly', function () {
    config(['services.google.client_id' => 'test-client', 'services.google.client_secret' => 'test-secret']);
    Http::fake(['oauth2.googleapis.com/token' => Http::response(['error' => 'invalid_grant'], 400)]);

    completeCallback()->assertRedirectContains('error=');

    expect(User::count())->toBe(0);
});
