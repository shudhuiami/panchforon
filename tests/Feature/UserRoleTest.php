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

test('the admin factory state marks the account admin both ways', function () {
    $user = User::factory()->admin()->create();

    expect($user->is_admin)->toBeTrue();

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'is_admin' => true,
        'role' => UserRole::Admin->value,
    ]);
});

test('the creator factory state makes a creator and nothing more', function () {
    $user = User::factory()->creator()->create();

    expect($user->is_admin)->toBeFalse();

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'is_admin' => false,
        'role' => UserRole::Creator->value,
    ]);
});

/**
 * The migration reads is_admin to decide role, so an account the application
 * still makes an admin the old way has to come out carrying the admin role.
 * This is the contract chunk 3 leans on before it drops is_admin altogether.
 */
test('a user made an admin through is_admin comes out with the admin role', function () {
    $user = User::factory()->create(['is_admin' => true]);

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'is_admin' => true,
        'role' => UserRole::Admin->value,
    ]);
});

test('no user is an admin by one column and not the other', function () {
    User::factory()->admin()->create();
    User::factory()->creator()->create();
    User::factory()->count(2)->create();

    $disagreeing = User::query()
        ->where('is_admin', true)
        ->where('role', '!=', UserRole::Admin->value)
        ->count();

    expect($disagreeing)->toBe(0)
        ->and(User::query()->where('role', UserRole::Admin->value)->count())->toBe(1);
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
