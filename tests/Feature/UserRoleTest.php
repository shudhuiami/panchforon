<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a new account is a member until someone says otherwise', function () {
    $user = User::factory()->create();

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'role' => UserRole::Member->value,
    ]);
});

test('the admin factory state produces an admin', function () {
    $user = User::factory()->admin()->create();

    expect($user->role)->toBe(UserRole::Admin)
        ->and($user->isAdmin())->toBeTrue()
        ->and($user->isCreator())->toBeTrue('an admin may do anything a creator may');

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'role' => UserRole::Admin->value,
    ]);
});

test('the creator factory state makes a creator and nothing more', function () {
    $user = User::factory()->creator()->create();

    expect($user->role)->toBe(UserRole::Creator)
        ->and($user->isCreator())->toBeTrue()
        ->and($user->isAdmin())->toBeFalse()
        ->and($user->isActiveAdmin())->toBeFalse();

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'role' => UserRole::Creator->value,
    ]);
});

test('a model that has never seen the database still has a role', function () {
    /**
     * The column default only applies on insert, so without the model's own
     * default this would be null and the first predicate to ask it anything
     * would fail.
     */
    expect(User::factory()->make()->role)->toBe(UserRole::Member);
});

test('suspension outranks the role', function () {
    $suspended = User::factory()->creator()->suspended()->create();

    expect($suspended->isCreator())->toBeTrue('the role itself is unchanged')
        ->and($suspended->canPublishWithoutReview())->toBeFalse()
        ->and(User::factory()->admin()->suspended()->create()->isActiveAdmin())->toBeFalse();
});

test('only creators and admins publish without review', function () {
    expect(UserRole::Member->publishesWithoutReview())->toBeFalse()
        ->and(UserRole::Creator->publishesWithoutReview())->toBeTrue()
        ->and(UserRole::Admin->publishesWithoutReview())->toBeTrue();
});

test('only creators and admins reach the studio', function () {
    expect(UserRole::Member->reachesStudio())->toBeFalse()
        ->and(UserRole::Creator->reachesStudio())->toBeTrue()
        ->and(UserRole::Admin->reachesStudio())->toBeTrue();
});

test('every role has a label and a Filament colour', function () {
    foreach (UserRole::cases() as $role) {
        expect($role->label())->not->toBeEmpty()
            ->and($role->color())->toBeIn(['gray', 'info', 'success', 'warning', 'danger']);
    }
});
