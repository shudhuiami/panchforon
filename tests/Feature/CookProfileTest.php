<?php

use App\Models\Rating;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a cook page shows who they are and what they have published', function () {
    $cook = User::factory()->create(['name' => 'Ahmed Zobayer']);
    $published = Recipe::factory()->count(2)->create(['user_id' => $cook->id]);
    Recipe::factory()->awaitingModeration()->create(['user_id' => $cook->id]);
    Rating::factory()->create(['user_id' => $cook->id]);

    $json = $this->getJson("/api/cooks/{$cook->id}")->assertOk()->json('data');

    expect($json['cook']['name'])->toBe('Ahmed Zobayer')
        ->and($json['cook']['recipes_count'])->toBe(2)
        ->and($json['cook']['ratings_count'])->toBe(1)
        ->and(collect($json['recipes']['data'])->pluck('id')->all())->toEqualCanonicalizing($published->pluck('id')->all());
});

test('a cook page never exposes the email or account flags', function () {
    $cook = User::factory()->create(['email' => 'private@panchforon.test', 'is_admin' => true]);

    $json = $this->getJson("/api/cooks/{$cook->id}")->assertOk()->json('data.cook');

    expect(array_keys($json))->toBe(['id', 'name', 'recipes_count', 'ratings_count', 'created_at']);
});

test('a suspended cook has no public page', function () {
    $cook = User::factory()->create(['suspended_at' => now(), 'suspension_reason' => 'Spam']);

    $this->getJson("/api/cooks/{$cook->id}")->assertNotFound();
});
