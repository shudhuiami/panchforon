<?php

use App\Enums\CreatorApplicationStatus;
use App\Enums\UserRole;
use App\Filament\Resources\CreatorApplications\Pages\ListCreatorApplications;
use App\Models\CreatorApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * An application the endpoint accepts, so each test only says what it changes.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function creatorApplicationPayload(array $overrides = []): array
{
    return $overrides + [
        'pitch' => 'Twelve years of Bengali home cooking, and a kitchen that never stops.',
        'youtube_channel_url' => 'https://www.youtube.com/@ranna-ghor',
    ];
}

test('a member applies and lands in the queue as pending', function () {
    $member = User::factory()->create();
    Sanctum::actingAs($member);

    $this->postJson('/api/creator-applications', creatorApplicationPayload())
        ->assertCreated()
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.youtube_channel_url', 'https://www.youtube.com/@ranna-ghor');

    $application = CreatorApplication::sole();

    expect($application->user_id)->toBe($member->id)
        ->and($application->status)->toBe(CreatorApplicationStatus::Pending)
        ->and($application->pitch)->toContain('Bengali home cooking')
        ->and($application->reviewed_by)->toBeNull()
        ->and($member->refresh()->role)->toBe(UserRole::Member);
});

test('a pitch is required and capped', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/creator-applications', ['pitch' => ''])
        ->assertStatus(422)
        ->assertJsonValidationErrors('pitch');

    $this->postJson('/api/creator-applications', creatorApplicationPayload(['pitch' => str_repeat('a', 2001)]))
        ->assertStatus(422)
        ->assertJsonValidationErrors('pitch');

    $this->postJson('/api/creator-applications', creatorApplicationPayload(['youtube_channel_url' => 'not-a-link']))
        ->assertStatus(422)
        ->assertJsonValidationErrors('youtube_channel_url');

    expect(CreatorApplication::count())->toBe(0);
});

test('applying twice updates the open application rather than duplicating it', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/creator-applications', creatorApplicationPayload(['pitch' => 'First attempt.']))->assertCreated();
    $this->postJson('/api/creator-applications', creatorApplicationPayload(['pitch' => 'Second, stronger attempt.']))->assertCreated();

    expect(CreatorApplication::count())->toBe(1)
        ->and(CreatorApplication::sole()->pitch)->toBe('Second, stronger attempt.');
});

test('two cooks applying make two applications', function () {
    foreach (User::factory()->count(2)->create() as $member) {
        Sanctum::actingAs($member);
        $this->postJson('/api/creator-applications', creatorApplicationPayload())->assertCreated();
    }

    expect(CreatorApplication::count())->toBe(2);
});

test('someone who can already post has nothing to apply for', function (User $user) {
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/creator-applications', creatorApplicationPayload())->assertForbidden();

    expect($response->json('message'))->toContain('already post')
        ->and(CreatorApplication::count())->toBe(0);
})->with([
    'a creator' => fn () => User::factory()->creator()->create(),
    'an admin' => fn () => User::factory()->admin()->create(),
]);

test('a suspended cook is stopped before the form is even read', function () {
    Sanctum::actingAs(User::factory()->suspended()->create());

    $this->postJson('/api/creator-applications', creatorApplicationPayload())
        ->assertForbidden()
        ->assertJsonPath('code', 'account_suspended');

    $this->getJson('/api/creator-applications/me')
        ->assertForbidden()
        ->assertJsonPath('code', 'account_suspended');

    expect(CreatorApplication::count())->toBe(0);
});

test('applying needs a signed-in cook', function () {
    $this->postJson('/api/creator-applications', creatorApplicationPayload())->assertUnauthorized();
    $this->getJson('/api/creator-applications/me')->assertUnauthorized();
});

test('me is empty for a cook who has never applied', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->getJson('/api/creator-applications/me')
        ->assertOk()
        ->assertJsonPath('data', null);
});

test('me returns the latest application and nobody else\'s', function () {
    $member = User::factory()->create();
    CreatorApplication::factory()->declined()->create(['user_id' => $member->id, 'pitch' => 'The first try.']);
    CreatorApplication::factory()->create(['user_id' => $member->id, 'pitch' => 'The one that counts.']);
    CreatorApplication::factory()->create(['pitch' => 'Somebody else entirely.']);

    Sanctum::actingAs($member);

    $this->getJson('/api/creator-applications/me')
        ->assertOk()
        ->assertJsonPath('data.pitch', 'The one that counts.')
        ->assertJsonPath('data.status', 'pending');
});

test('me never names the admin who decided', function () {
    $member = User::factory()->create();
    $reviewer = User::factory()->admin()->create(['name' => 'The Decider']);
    CreatorApplication::factory()->declined()->create([
        'user_id' => $member->id,
        'reviewed_by' => $reviewer->id,
        'review_note' => 'Come back with a few recipes written up.',
    ]);

    Sanctum::actingAs($member);

    $response = $this->getJson('/api/creator-applications/me')->assertOk();

    expect($response->json('data'))
        ->toHaveKeys(['id', 'status', 'pitch', 'youtube_channel_url', 'review_note', 'reviewed_at', 'created_at'])
        ->and($response->json('data'))->not->toHaveKey('reviewed_by')
        ->and($response->json('data'))->not->toHaveKey('reviewer')
        ->and($response->json('data'))->not->toHaveKey('user')
        ->and($response->json('data.review_note'))->toBe('Come back with a few recipes written up.')
        /** The decision carries a date, even though it never carries a name. */
        ->and($response->json('data.reviewed_at'))->toBeString();

    $response->assertDontSee('The Decider')->assertDontSee($reviewer->email);
});

test('a declined cook can apply again', function () {
    $member = User::factory()->create();
    CreatorApplication::factory()->declined()->create(['user_id' => $member->id, 'pitch' => 'The first try.']);

    Sanctum::actingAs($member);

    $this->postJson('/api/creator-applications', creatorApplicationPayload(['pitch' => 'A second, better try.']))
        ->assertCreated()
        ->assertJsonPath('data.status', 'pending');

    expect(CreatorApplication::count())->toBe(2);

    $this->getJson('/api/creator-applications/me')
        ->assertOk()
        ->assertJsonPath('data.pitch', 'A second, better try.')
        ->assertJsonPath('data.review_note', null);
});

/**
 * The queue is driven through Livewire, and Livewire's test harness cannot
 * follow a Laravel HTTP call made earlier in the same test, so the waiting
 * application is seeded rather than posted. What the endpoint writes is
 * covered by the first test in this file.
 */
test('approving through the admin action turns the applicant into a creator', function () {
    $member = User::factory()->create();
    $admin = User::factory()->admin()->create();

    CreatorApplication::factory()->pending()->create(['user_id' => $member->id]);

    Livewire::actingAs($admin)
        ->test(ListCreatorApplications::class)
        ->callTableAction('approve', CreatorApplication::sole(), ['review_note' => 'Lovely pitch.'])
        ->assertHasNoTableActionErrors();

    expect($member->refresh()->role)->toBe(UserRole::Creator);

    Sanctum::actingAs($member);

    $this->getJson('/api/creator-applications/me')
        ->assertOk()
        ->assertJsonPath('data.status', 'approved')
        ->assertJsonPath('data.review_note', 'Lovely pitch.');

    $this->postJson('/api/creator-applications', creatorApplicationPayload())->assertForbidden();
});
