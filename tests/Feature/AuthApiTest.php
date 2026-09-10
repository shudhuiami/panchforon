<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('registers a new user and returns token', function () {
    $response = $this->postJson('/api/register', [
        'name' => 'Ahmed Zobayer',
        'email' => 'ahmed@example.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'user' => ['id', 'name', 'email'],
            'token',
        ]);

    $this->assertDatabaseHas('users', [
        'email' => 'ahmed@example.com',
    ]);
});

test('registration validates unique email and password requirements', function () {
    User::factory()->create(['email' => 'existing@example.com']);

    $response = $this->postJson('/api/register', [
        'name' => 'Test User',
        'email' => 'existing@example.com',
        'password' => 'short',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email', 'password']);
});

test('logs in user with correct credentials and returns token', function () {
    $user = User::factory()->create([
        'email' => 'login@example.com',
        'password' => Hash::make('secret123'),
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'login@example.com',
        'password' => 'secret123',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'user' => ['id', 'email'],
            'token',
        ]);
});

test('login fails with invalid password', function () {
    User::factory()->create([
        'email' => 'user@example.com',
        'password' => Hash::make('correct_password'),
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'user@example.com',
        'password' => 'wrong_password',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('returns current user profile with sanctum auth', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson('/api/user');

    $response->assertStatus(200)
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.email', $user->email);
});

test('logs out and revokes access token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/logout');

    $response->assertStatus(200)
        ->assertJson(['message' => 'Logged out successfully']);

    expect($user->tokens()->count())->toBe(0);
});

test('unauthenticated request to protected endpoints returns 401', function () {
    $response = $this->getJson('/api/user');

    $response->assertStatus(401);
});
