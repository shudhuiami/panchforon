<?php

use App\Enums\ModerationStatus;
use App\Filament\Studio\Widgets\StudioOverview;
use App\Models\Recipe;
use App\Models\RecipeStat;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Filament::setCurrentPanel('studio');
});

/**
 * The widget's visible text, with every tag removed and whitespace collapsed.
 *
 * Asserting on the raw HTML does not work here: each stat renders a Heroicon,
 * and an SVG path is a long run of digits. A bare `assertDontSee('99')` passes
 * or fails on whether some icon's curve happens to contain `.993`, which has
 * nothing to do with the figure being tested. Stripping the tags throws the
 * path data away with them and leaves "Ratings received 41" to match against.
 */
function studioOverviewText(): string
{
    $html = Livewire::test(StudioOverview::class)
        ->assertSuccessful()
        ->html();

    return trim((string) preg_replace('/\s+/', ' ', strip_tags($html)));
}

test('the figures count the signed-in creator\'s work and nobody else\'s', function () {
    $creator = User::factory()->creator()->create();
    $other = User::factory()->creator()->create();

    Recipe::factory()->count(3)->for($creator)->create(['moderation_status' => ModerationStatus::Approved]);
    Recipe::factory()->count(2)->for($creator)->create(['moderation_status' => ModerationStatus::Draft]);
    Recipe::factory()->for($creator)->unpublished()->create();

    /** None of this belongs to them, so none of it may reach the dashboard. */
    Recipe::factory()->count(7)->for($other)->create(['moderation_status' => ModerationStatus::Approved]);
    Recipe::factory()->count(4)->fromApi()->create();

    $this->actingAs($creator);

    expect(studioOverviewText())
        ->toContain('Live 3')
        ->toContain('Drafts 2')
        ->toContain('Taken down 1');
});

test('the rating average is weighted by how many ratings each recipe has', function () {
    $creator = User::factory()->creator()->create();

    $popular = Recipe::factory()->for($creator)->create();
    $barelyRated = Recipe::factory()->for($creator)->create();

    /**
     * Averaging the averages would give 4.50. Weighting by count gives
     * (4.00 * 40 + 5.00 * 1) / 41 = 4.02, which is the honest figure.
     */
    RecipeStat::factory()->create([
        'recipe_id' => $popular->id,
        'ratings_count' => 40,
        'ratings_avg' => 4.00,
    ]);

    RecipeStat::factory()->create([
        'recipe_id' => $barelyRated->id,
        'ratings_count' => 1,
        'ratings_avg' => 5.00,
    ]);

    $this->actingAs($creator);

    expect(studioOverviewText())
        ->toContain('Ratings received 41')
        ->toContain('4.02 average across your recipes')
        ->not->toContain('4.50');
});

test('another creator\'s ratings are not counted', function () {
    $creator = User::factory()->creator()->create();
    $other = User::factory()->creator()->create();

    RecipeStat::factory()->create([
        'recipe_id' => Recipe::factory()->for($other)->create()->id,
        'ratings_count' => 99,
        'ratings_avg' => 5.00,
    ]);

    $this->actingAs($creator);

    expect(studioOverviewText())
        ->toContain('Ratings received 0')
        ->toContain('Nobody has rated your recipes yet')
        ->not->toContain('average across your recipes');
});

test('a creator with nothing written sees zeroes rather than an error', function () {
    $this->actingAs(User::factory()->creator()->create());

    expect(studioOverviewText())
        ->toContain('Live 0')
        ->toContain('Nothing half-written')
        ->toContain('Nothing removed')
        ->toContain('Nobody has rated your recipes yet');
});
