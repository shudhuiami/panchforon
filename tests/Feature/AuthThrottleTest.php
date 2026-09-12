<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('login is rate limited per ip', function () {
    $user = User::factory()->create(['password' => 'password']);

    foreach (range(1, 10) as $attempt) {
        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'wrong'])->assertStatus(422);
    }

    $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password'])
        ->assertStatus(429)
        ->assertHeader('Retry-After');
});

test('registration is rate limited per ip', function () {
    foreach (range(1, 10) as $attempt) {
        $this->postJson('/api/register', ['name' => '', 'email' => 'nope', 'password' => 'x'])->assertStatus(422);
    }

    $this->postJson('/api/register', [
        'name' => 'Late Comer',
        'email' => 'late@example.com',
        'password' => 'password123',
    ])->assertStatus(429);

    expect(User::query()->where('email', 'late@example.com')->exists())->toBeFalse();
});
