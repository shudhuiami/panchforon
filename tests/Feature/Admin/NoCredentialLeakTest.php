<?php

use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Schemas\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * The admin panel must never put a password hash or a remember token into the
 * rendered page or the Livewire payload. Filament fills form fields from the
 * model, so a stray field declaration is all it would take.
 */
test('no admin page renders a password hash or remember token', function () {
    $admin = User::factory()->admin()->create();
    $member = User::factory()->create([
        'password' => 'a-known-password',
        'remember_token' => 'known-remember-token-value',
    ]);

    $member->refresh();

    $paths = [
        '/admin/users',
        "/admin/users/{$member->getKey()}",
        "/admin/users/{$member->getKey()}/edit",
    ];

    foreach ($paths as $path) {
        $body = $this->actingAs($admin)->get($path)->assertSuccessful()->getContent();

        expect($body)
            ->not->toContain($member->password)
            ->not->toContain($admin->password)
            ->not->toContain('known-remember-token-value')
            ->and($body)->not->toMatch('/\$2[aby]\$\d{2}\$/');
    }
});

test('the user resource exposes no credential fields', function () {
    $schema = UserResource::form(
        Schema::make(
            Livewire\Livewire::new(EditUser::class)
        )
    );

    $names = collect($schema->getFlatComponents())
        ->map(fn ($component) => method_exists($component, 'getName') ? $component->getName() : null)
        ->filter()
        ->all();

    expect($names)
        ->not->toContain('password')
        ->not->toContain('remember_token')
        ->toContain('name')
        ->toContain('email')
        ->toContain('is_admin');
});
