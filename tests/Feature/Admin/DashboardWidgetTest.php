<?php

use App\Filament\Widgets\PlatformOverview;
use App\Models\Rating;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * @return array<string, string>
 */
function dashboardStats(): array
{
    return collect((fn (): array => $this->getStats())->call(new PlatformOverview))
        ->mapWithKeys(fn ($stat): array => [$stat->getLabel() => $stat->getValue()])
        ->all();
}

test('the dashboard reports the four landing figures', function () {
    /**
     * Built explicitly rather than with nested factories, because a Recipe or
     * Rating factory creates its own author and would inflate the user count.
     */
    $admin = User::factory()->admin()->create();
    $members = User::factory()->count(4)->create();
    $suspended = User::factory()->suspended()->create();

    Recipe::factory()->count(2)->fromApi()->create();
    Recipe::factory()->count(3)->awaitingModeration()->for($members->first())->create();
    $published = Recipe::factory()->for($members->first())->create();

    foreach ($members as $member) {
        Rating::factory()->create([
            'user_id' => $member->getKey(),
            'recipe_id' => $published->getKey(),
        ]);
    }

    Rating::factory()
        ->create(['user_id' => $suspended->getKey(), 'recipe_id' => $published->getKey()])
        ->forceFill(['created_at' => now()->subMonth()])
        ->save();

    expect(User::query()->count())->toBe(6)
        ->and(Recipe::query()->count())->toBe(6)
        ->and(Rating::query()->count())->toBe(5);

    $stats = dashboardStats();

    expect($stats['Total users'])->toBe('6')
        ->and($stats['Total recipes'])->toBe('6')
        ->and($stats['Awaiting moderation'])->toBe('3')
        ->and($stats['Ratings this week'])->toBe('4');

    $this->actingAs($admin);

    Livewire::test(PlatformOverview::class)
        ->assertSee('Total users')
        ->assertSee('Total recipes')
        ->assertSee('Awaiting moderation')
        ->assertSee('Ratings this week');
});

test('an empty install reports zeroes rather than failing', function () {
    $stats = dashboardStats();

    expect($stats['Total users'])->toBe('0')
        ->and($stats['Total recipes'])->toBe('0')
        ->and($stats['Awaiting moderation'])->toBe('0')
        ->and($stats['Ratings this week'])->toBe('0');
});

test('the dashboard renders for an admin and is refused to everyone else', function () {
    $this->actingAs(User::factory()->admin()->create())->get('/admin')->assertSuccessful();
    $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
});
