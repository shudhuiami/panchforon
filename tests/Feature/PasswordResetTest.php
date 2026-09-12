<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

uses(RefreshDatabase::class);

test('asking for a reset link sends one', function () {
    Notification::fake();
    $user = User::factory()->create(['email' => 'cook@panchforon.test']);

    $this->postJson('/api/forgot-password', ['email' => 'cook@panchforon.test'])->assertOk();

    Notification::assertSentTo($user, ResetPassword::class);
});

test('the reset link opens the single-page app', function () {
    Notification::fake();
    $user = User::factory()->create(['email' => 'cook@panchforon.test']);

    $this->postJson('/api/forgot-password', ['email' => 'cook@panchforon.test'])->assertOk();

    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
        $mail = $notification->toMail($user);

        return str_contains((string) $mail->actionUrl, '/reset-password?token=')
            && str_contains((string) $mail->actionUrl, urlencode($user->email));
    });
});

test('an unknown address gets the same reply, so accounts stay private', function () {
    Notification::fake();

    $this->postJson('/api/forgot-password', ['email' => 'nobody@panchforon.test'])
        ->assertOk()
        ->assertJsonPath('message', 'If that address has an account, a reset link is on its way.');

    Notification::assertNothingSent();
});

test('a valid token resets the password and signs every device out', function () {
    $user = User::factory()->create(['email' => 'cook@panchforon.test', 'password' => Hash::make('old-password')]);
    $user->createToken('auth-token');
    $token = Password::createToken($user);

    $this->postJson('/api/reset-password', [
        'token' => $token,
        'email' => 'cook@panchforon.test',
        'password' => 'a-brand-new-one',
        'password_confirmation' => 'a-brand-new-one',
    ])->assertOk();

    expect(Hash::check('a-brand-new-one', $user->fresh()->password))->toBeTrue()
        ->and($user->tokens()->count())->toBe(0);
});

test('a bad token changes nothing', function () {
    $user = User::factory()->create(['email' => 'cook@panchforon.test', 'password' => Hash::make('old-password')]);

    $this->postJson('/api/reset-password', [
        'token' => 'not-a-real-token',
        'email' => 'cook@panchforon.test',
        'password' => 'a-brand-new-one',
        'password_confirmation' => 'a-brand-new-one',
    ])->assertStatus(422);

    expect(Hash::check('old-password', $user->fresh()->password))->toBeTrue();
});
