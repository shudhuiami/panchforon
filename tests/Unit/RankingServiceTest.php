<?php

use App\Models\Rating;
use App\Models\Recipe;
use App\Models\User;
use App\Services\RankingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->rankingService = new RankingService;
});

// Case 1: Recipe with 0 ratings -> score equals global mean
test('case 1: recipe with 0 ratings scores equal to global mean', function () {
    $score = $this->rankingService->calculateScore(v: 0, R: 0.0, m: 10, C: 3.8);

    expect($score)->toBe(3.8);
});

// Case 2: Recipe with 1 five-star rating scores below a recipe with 200 ratings averaging 4.6
test('case 2: recipe with 1 five-star rating scores below well-reviewed recipe with 200 ratings averaging 4.6', function () {
    // Recipe A: 1 five-star rating
    $scoreA = $this->rankingService->calculateScore(v: 1, R: 5.0, m: 10, C: 3.8);

    // Recipe B: 200 ratings averaging 4.6
    $scoreB = $this->rankingService->calculateScore(v: 200, R: 4.6, m: 10, C: 3.8);

    // 3.9091 < 4.5619
    expect($scoreA)->toBeLessThan($scoreB)
        ->and($scoreA)->toBe(3.9091)
        ->and($scoreB)->toBe(4.5619);
});

// Case 3: Recipe with 1000 ratings -> score â‰ˆ raw average (within 0.01)
test('case 3: recipe with 1000 ratings converges to raw average within 0.01', function () {
    $rawAvg = 4.65;
    $score = $this->rankingService->calculateScore(v: 1000, R: $rawAvg, m: 10, C: 3.8);

    expect(abs($score - $rawAvg))->toBeLessThan(0.01);
});

// Case 4: Rating update changes the score; rating count doesn't double-increment
test('case 4: rating update changes the score without double-incrementing rating count', function () {
    $user = User::factory()->create();
    $recipe = Recipe::factory()->create();

    // Initial rating: 5 stars
    $rating = Rating::create([
        'user_id' => $user->id,
        'recipe_id' => $recipe->id,
        'stars' => 5,
        'review' => 'Great!',
    ]);

    $statAfterFirst = $recipe->fresh()->stat;
    expect($statAfterFirst->ratings_count)->toBe(1)
        ->and($statAfterFirst->ratings_avg)->toBe(5.0);

    // Update rating in place: change to 2 stars
    $rating->update([
        'stars' => 2,
    ]);

    $statAfterUpdate = $recipe->fresh()->stat;
    expect($statAfterUpdate->ratings_count)->toBe(1)
        ->and($statAfterUpdate->ratings_avg)->toBe(2.0)
        ->and($statAfterUpdate->bayesian_score)->toBeLessThan($statAfterFirst->bayesian_score);
});

// Case 5: All recipes unrated -> no division by zero
test('case 5: all recipes unrated causes no division by zero and returns default mean', function () {
    $score = $this->rankingService->calculateScore(v: 0, R: 0.0, m: 10, C: 3.8);
    expect($score)->toBe(3.8);

    // Edge case: m=0, v=0
    $edgeScore = $this->rankingService->calculateScore(v: 0, R: 0.0, m: 0, C: 3.5);
    expect($edgeScore)->toBe(3.5);
});
