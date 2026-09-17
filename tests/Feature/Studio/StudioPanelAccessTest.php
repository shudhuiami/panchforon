<?php

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * The studio's own pages. As with the admin panel, these go through the real
 * routes: authorization is Filament's Authenticate middleware calling
 * User::canAccessPanel(), and only a real request exercises that.
 */
function studioPaths(): array
{
    return [
        '/studio',
    ];
}

test('a guest is sent to the studio sign-in screen', function () {
    foreach (studioPaths() as $path) {
        $this->get($path)->assertRedirect('/studio/login');
    }
});

test('a member who has not been made a creator is refused the studio', function () {
    $member = User::factory()->create();

    foreach (studioPaths() as $path) {
        $this->actingAs($member)->get($path)->assertForbidden();
    }
});

test('a suspended creator is refused the studio', function () {
    $suspended = User::factory()->creator()->suspended()->create();

    foreach (studioPaths() as $path) {
        $this->actingAs($suspended)->get($path)->assertForbidden();
    }
});

test('a creator reaches the studio', function () {
    $creator = User::factory()->creator()->create();

    foreach (studioPaths() as $path) {
        $this->actingAs($creator)->get($path)->assertSuccessful();
    }
});

test('an admin reaches the studio too', function () {
    /** Anything a creator may do, an admin may do — including writing a recipe. */
    $admin = User::factory()->admin()->create();

    foreach (studioPaths() as $path) {
        $this->actingAs($admin)->get($path)->assertSuccessful();
    }
});

test('a creator is still refused the admin panel', function () {
    $creator = User::factory()->creator()->create();

    $this->actingAs($creator)->get('/admin')->assertForbidden();
    $this->actingAs($creator)->get('/admin/users')->assertForbidden();
});

test('each panel asks its own question of canAccessPanel', function () {
    $admin = Filament::getPanel('admin');
    $studio = Filament::getPanel('studio');

    $member = User::factory()->make();
    $creator = User::factory()->creator()->make();
    $administrator = User::factory()->admin()->make();
    $suspendedCreator = User::factory()->creator()->suspended()->make();

    expect($member->canAccessPanel($admin))->toBeFalse()
        ->and($member->canAccessPanel($studio))->toBeFalse()
        ->and($creator->canAccessPanel($admin))->toBeFalse()
        ->and($creator->canAccessPanel($studio))->toBeTrue()
        ->and($administrator->canAccessPanel($admin))->toBeTrue()
        ->and($administrator->canAccessPanel($studio))->toBeTrue()
        ->and($suspendedCreator->canAccessPanel($studio))->toBeFalse();
});
