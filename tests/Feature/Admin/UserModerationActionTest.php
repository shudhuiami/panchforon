<?php

use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('an admin can suspend an account, which revokes its tokens', function () {
    $admin = User::factory()->admin()->create();
    $member = User::factory()->create();
    $member->createToken('auth-token');

    expect($member->tokens()->count())->toBe(1);

    $this->actingAs($admin);

    Livewire::test(ListUsers::class)
        ->callAction(
            TestAction::make('suspend')->table($member),
            ['suspension_reason' => 'Posting spam recipes'],
        )
        ->assertHasNoActionErrors();

    $member->refresh();

    expect($member->isSuspended())->toBeTrue()
        ->and($member->suspension_reason)->toBe('Posting spam recipes')
        ->and($member->tokens()->count())->toBe(0);
});

test('suspending requires a reason', function () {
    $admin = User::factory()->admin()->create();
    $member = User::factory()->create();

    $this->actingAs($admin);

    Livewire::test(ListUsers::class)
        ->callAction(TestAction::make('suspend')->table($member), ['suspension_reason' => ''])
        ->assertHasActionErrors(['suspension_reason']);

    expect($member->fresh()->isSuspended())->toBeFalse();
});

test('an admin can lift a suspension', function () {
    $admin = User::factory()->admin()->create();
    $member = User::factory()->suspended()->create();

    $this->actingAs($admin);

    Livewire::test(ListUsers::class)
        ->callAction(TestAction::make('liftSuspension')->table($member))
        ->assertHasNoActionErrors();

    $member->refresh();

    expect($member->isSuspended())->toBeFalse()
        ->and($member->suspension_reason)->toBeNull();
});

test('an admin can grant and revoke admin access', function () {
    $admin = User::factory()->admin()->create();
    $member = User::factory()->create();

    $this->actingAs($admin);

    Livewire::test(ListUsers::class)
        ->callAction(TestAction::make('toggleAdmin')->table($member));

    expect($member->fresh()->is_admin)->toBeTrue();

    Livewire::test(ListUsers::class)
        ->callAction(TestAction::make('toggleAdmin')->table($member->fresh()));

    expect($member->fresh()->is_admin)->toBeFalse();
});

test('an admin cannot suspend or demote their own account', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    Livewire::test(ListUsers::class)
        ->assertActionHidden(TestAction::make('suspend')->table($admin))
        ->assertActionHidden(TestAction::make('toggleAdmin')->table($admin));

    $admin->refresh();

    expect($admin->is_admin)->toBeTrue()
        ->and($admin->isSuspended())->toBeFalse();
});

/**
 * Reaching the Livewire component directly bypasses the panel's HTTP
 * middleware, so the resource policy is what refuses these. Filament aborts
 * during mount and Livewire surfaces that as a 403 response rather than an
 * exception, so no user data is ever rendered.
 */
test('a non-admin is refused the user list component and leaks no data', function () {
    $member = User::factory()->create();
    $victim = User::factory()->create([
        'name' => 'Zzyzx Victim',
        'email' => 'victim@secret.example',
    ]);

    $this->actingAs($member);

    $component = Livewire::test(ListUsers::class)->assertForbidden();

    expect($component->html())
        ->not->toContain('Zzyzx Victim')
        ->not->toContain('victim@secret.example');
});

test('a non-admin calling a user action changes nothing', function () {
    $member = User::factory()->create();
    $victim = User::factory()->create();

    $this->actingAs($member);

    rescue(fn () => Livewire::test(ListUsers::class)->callAction(
        TestAction::make('suspend')->table($victim),
        ['suspension_reason' => 'I should not be able to do this'],
    ), report: false);

    expect($victim->fresh()->isSuspended())->toBeFalse()
        ->and($victim->fresh()->is_admin)->toBeFalse();
});

test('a suspended admin is refused the user list component', function () {
    $this->actingAs(User::factory()->admin()->suspended()->create());

    Livewire::test(ListUsers::class)->assertForbidden();
});

test('a suspended admin calling a user action changes nothing', function () {
    $suspended = User::factory()->admin()->suspended()->create();
    $victim = User::factory()->create();

    $this->actingAs($suspended);

    rescue(fn () => Livewire::test(ListUsers::class)->callAction(
        TestAction::make('suspend')->table($victim),
        ['suspension_reason' => 'Still should not work'],
    ), report: false);

    expect($victim->fresh()->isSuspended())->toBeFalse();
});

test('the policy refuses non-admins and self-moderation', function () {
    $admin = User::factory()->admin()->create();
    $member = User::factory()->create();
    $suspendedAdmin = User::factory()->admin()->suspended()->create();

    expect(Gate::forUser($admin)->allows('moderate', $member))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('moderate', $admin))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('delete', $admin))->toBeFalse()
        ->and(Gate::forUser($member)->allows('moderate', $admin))->toBeFalse()
        ->and(Gate::forUser($member)->allows('viewAny', User::class))->toBeFalse()
        ->and(Gate::forUser($suspendedAdmin)->allows('viewAny', User::class))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('create', User::class))->toBeFalse();
});
