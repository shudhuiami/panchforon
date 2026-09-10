<?php

use App\Models\Recipe;
use App\Models\User;
use Filament\Auth\Pages\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Every page the panel exposes. Authorization is enforced by Filament's
 * Authenticate middleware calling User::canAccessPanel(), so these are checked
 * against the real routes rather than the guard in isolation.
 */
function adminPaths(): array
{
    $user = User::factory()->create();
    $recipe = Recipe::factory()->create();

    return [
        '/admin',
        '/admin/users',
        "/admin/users/{$user->getKey()}",
        "/admin/users/{$user->getKey()}/edit",
        '/admin/recipes',
        "/admin/recipes/{$recipe->getKey()}",
        "/admin/recipes/{$recipe->getKey()}/edit",
        '/admin/site-settings',
    ];
}

test('a guest is redirected to the panel login screen', function () {
    foreach (adminPaths() as $path) {
        $this->get($path)->assertRedirect('/admin/login');
    }
});

test('a signed-in non-admin is refused every admin page', function () {
    $member = User::factory()->create();

    foreach (adminPaths() as $path) {
        $this->actingAs($member)->get($path)->assertForbidden();
    }
});

test('a suspended admin is refused every admin page', function () {
    $suspended = User::factory()->admin()->suspended()->create();

    foreach (adminPaths() as $path) {
        $this->actingAs($suspended)->get($path)->assertForbidden();
    }
});

test('an admin reaches every admin page', function () {
    $admin = User::factory()->admin()->create();

    foreach (adminPaths() as $path) {
        $this->actingAs($admin)->get($path)->assertSuccessful();
    }
});

test('canAccessPanel is the single gate for the panel', function () {
    $panel = Filament\Facades\Filament::getPanel('admin');

    expect(User::factory()->admin()->make()->canAccessPanel($panel))->toBeTrue()
        ->and(User::factory()->make()->canAccessPanel($panel))->toBeFalse()
        ->and(User::factory()->admin()->suspended()->make()->canAccessPanel($panel))->toBeFalse()
        ->and(User::factory()->suspended()->make()->canAccessPanel($panel))->toBeFalse();
});

test('a non-admin cannot log in through the panel login form', function () {
    $member = User::factory()->create(['password' => 'password']);

    Livewire\Livewire::test(Login::class)
        ->fillForm([
            'email' => $member->email,
            'password' => 'password',
        ])
        ->call('authenticate')
        ->assertHasFormErrors();

    expect(auth()->check())->toBeFalse();
});

test('an admin can log in through the panel login form', function () {
    $admin = User::factory()->admin()->create(['password' => 'password']);

    Livewire\Livewire::test(Login::class)
        ->fillForm([
            'email' => $admin->email,
            'password' => 'password',
        ])
        ->call('authenticate')
        ->assertHasNoFormErrors();

    expect(auth()->id())->toBe($admin->getKey());
});
