<?php

use App\Filament\Pages\Reports;
use App\Filament\Pages\SiteSettings;
use App\Filament\Resources\ContentBlocks\ContentBlockResource;
use App\Filament\Resources\ContentFlags\ContentFlagResource;
use App\Filament\Resources\Pages\PageResource;
use App\Filament\Resources\Recipes\RecipeResource;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Widgets\TopRecipes;
use App\Models\ContentFlag;
use App\Models\Rating;
use App\Models\Recipe;
use App\Models\RecipeStat;
use App\Models\User;
use App\Services\GrowthReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('an ordinary cook cannot reach the reports', function () {
    $this->actingAs(User::factory()->create())->get(Reports::getUrl())->assertForbidden();
});

test('a suspended admin cannot reach the reports', function () {
    $suspended = User::factory()->create(['is_admin' => true, 'suspended_at' => now(), 'suspension_reason' => 'Abuse']);

    $this->actingAs($suspended)->get(Reports::getUrl())->assertForbidden();
});

test('an admin can open the reports', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]))->get(Reports::getUrl())->assertOk();
});

test('the weekly figures count each record in the week it happened', function () {
    $thisWeek = now()->startOfWeek();
    Recipe::factory()->create(['created_at' => $thisWeek->copy()->addDay()]);
    Recipe::factory()->count(2)->create(['created_at' => $thisWeek->copy()->subWeek()->addDay()]);
    Recipe::factory()->create(['created_at' => $thisWeek->copy()->subWeeks(20)]);

    $weekly = app(GrowthReport::class)->weekly(12);

    expect($weekly['labels'])->toHaveCount(12)
        ->and($weekly['recipes'])->toHaveCount(12)
        ->and(end($weekly['recipes']))->toBe(1)
        ->and($weekly['recipes'][10])->toBe(2)
        // Anything older than the window is left out rather than folded into the first week.
        ->and(array_sum($weekly['recipes']))->toBe(3);
});

test('the cuisine breakdown counts only what the catalogue shows, largest first', function () {
    Recipe::factory()->count(3)->create(['cuisine' => 'Bangladeshi']);
    Recipe::factory()->count(1)->create(['cuisine' => 'Thai']);
    Recipe::factory()->awaitingModeration()->count(5)->create(['cuisine' => 'Thai']);

    $breakdown = app(GrowthReport::class)->cuisineBreakdown();

    expect($breakdown['labels'])->toBe(['Bangladeshi', 'Thai'])
        ->and($breakdown['counts'])->toBe([3, 1]);
});

test('the thirty-day summary reports the unrated share of the catalogue', function () {
    $rated = Recipe::factory()->create();
    RecipeStat::factory()->create(['recipe_id' => $rated->id, 'ratings_count' => 1]);
    Rating::factory()->create(['recipe_id' => $rated->id]);
    Recipe::factory()->count(3)->create();
    ContentFlag::factory()->create(['recipe_id' => $rated->id]);

    $summary = app(GrowthReport::class)->lastThirtyDays();

    expect($summary['new_recipes'])->toBe(4)
        ->and($summary['open_flags'])->toBe(1)
        ->and($summary['unrated_share'])->toBe(75.0);
});

test('the unrated share is zero rather than a division by zero on an empty catalogue', function () {
    expect(app(GrowthReport::class)->lastThirtyDays()['unrated_share'])->toBe(0.0);
});

test('best rated lists only recipes that have been rated', function () {
    $rated = Recipe::factory()->create(['title' => 'Chittagong Beef Kala Bhuna']);
    RecipeStat::factory()->create(['recipe_id' => $rated->id, 'ratings_count' => 4, 'ratings_avg' => 4.8, 'bayesian_score' => 4.6]);
    $unrated = Recipe::factory()->create(['title' => 'Nobody Has Cooked This']);
    RecipeStat::factory()->create(['recipe_id' => $unrated->id, 'ratings_count' => 0]);

    Livewire::actingAs(User::factory()->create(['is_admin' => true]))
        ->test(TopRecipes::class)
        ->assertCanSeeTableRecords([$rated])
        ->assertCanNotSeeTableRecords([$unrated]);
});

test('no two sidebar items share a label', function () {
    $labels = [
        UserResource::getNavigationLabel(),
        RecipeResource::getNavigationLabel(),
        ContentFlagResource::getNavigationLabel(),
        PageResource::getNavigationLabel(),
        ContentBlockResource::getNavigationLabel(),
        Reports::getNavigationLabel(),
        SiteSettings::getNavigationLabel(),
    ];

    expect($labels)->toBe(array_unique($labels));
});
