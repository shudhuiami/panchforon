<?php

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('seeding creates an admin that can reach the panel', function () {
    $this->seed(AdminUserSeeder::class);

    $admin = User::query()->where('email', config('admin.seed_email'))->sole();

    expect($admin->is_admin)->toBeTrue()
        ->and($admin->isSuspended())->toBeFalse();

    $this->actingAs($admin)->get('/admin')->assertSuccessful();
});

test('seeding twice does not fail or reset the password', function () {
    $this->seed(AdminUserSeeder::class);

    $admin = User::query()->where('email', config('admin.seed_email'))->sole();
    $admin->forceFill(['password' => Hash::make('a-password-the-admin-chose')])->save();

    $this->seed(AdminUserSeeder::class);

    expect(User::query()->where('email', config('admin.seed_email'))->count())->toBe(1)
        ->and(Hash::check('a-password-the-admin-chose', $admin->fresh()->password))->toBeTrue();
});

test('seeding promotes an existing account rather than duplicating it', function () {
    $existing = User::factory()->create([
        'email' => config('admin.seed_email'),
        'name' => 'Already Registered',
    ]);

    $this->seed(AdminUserSeeder::class);

    $existing->refresh();

    expect($existing->is_admin)->toBeTrue()
        ->and($existing->name)->toBe('Already Registered')
        ->and(User::query()->count())->toBe(1);
});

test('seeding lifts a suspension on the seeded admin account', function () {
    User::factory()->suspended()->create(['email' => config('admin.seed_email')]);

    $this->seed(AdminUserSeeder::class);

    $admin = User::query()->where('email', config('admin.seed_email'))->sole();

    expect($admin->isSuspended())->toBeFalse()
        ->and($admin->is_admin)->toBeTrue();
});
