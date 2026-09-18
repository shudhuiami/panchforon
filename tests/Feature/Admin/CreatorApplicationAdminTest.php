<?php

use App\Enums\CreatorApplicationStatus;
use App\Enums\UserRole;
use App\Filament\Resources\CreatorApplications\CreatorApplicationResource;
use App\Filament\Resources\CreatorApplications\Pages\ListCreatorApplications;
use App\Models\CreatorApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

test('an ordinary cook cannot reach the application queue', function () {
    $this->actingAs(User::factory()->create())
        ->get(CreatorApplicationResource::getUrl('index'))
        ->assertForbidden();
});

test('a creator cannot read the applications of the people behind them', function () {
    CreatorApplication::factory()->create(['pitch' => 'Twelve years of Bengali home cooking.']);

    $this->actingAs(User::factory()->creator()->create())
        ->get(CreatorApplicationResource::getUrl('index'))
        ->assertForbidden()
        ->assertDontSee('Twelve years of Bengali home cooking.', false);
});

test('a suspended admin cannot reach the application queue', function () {
    $suspended = User::factory()->admin()->suspended()->create();

    $this->actingAs($suspended)
        ->get(CreatorApplicationResource::getUrl('index'))
        ->assertForbidden();
});

test('the queue opens on the applications still waiting', function () {
    $pending = CreatorApplication::factory()->create();
    $approved = CreatorApplication::factory()->approved()->create();

    $this->actingAs($this->admin)
        ->get(CreatorApplicationResource::getUrl('index'))
        ->assertOk();

    Livewire::actingAs($this->admin)
        ->test(ListCreatorApplications::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$pending])
        ->assertCanNotSeeTableRecords([$approved]);
});

test('each outcome has a tab of its own', function () {
    $pending = CreatorApplication::factory()->create();
    $approved = CreatorApplication::factory()->approved()->create();
    $declined = CreatorApplication::factory()->declined()->create();

    Livewire::actingAs($this->admin)
        ->test(ListCreatorApplications::class, ['activeTab' => 'approved'])
        ->assertCanSeeTableRecords([$approved])
        ->assertCanNotSeeTableRecords([$pending, $declined]);

    Livewire::actingAs($this->admin)
        ->test(ListCreatorApplications::class, ['activeTab' => 'declined'])
        ->assertCanSeeTableRecords([$declined])
        ->assertCanNotSeeTableRecords([$pending, $approved]);

    Livewire::actingAs($this->admin)
        ->test(ListCreatorApplications::class, ['activeTab' => 'all'])
        ->assertCanSeeTableRecords([$pending, $approved, $declined]);
});

test('approving makes the applicant a creator and stamps the decision', function () {
    $applicant = User::factory()->create();
    $application = CreatorApplication::factory()->create(['user_id' => $applicant->id]);

    Livewire::actingAs($this->admin)
        ->test(ListCreatorApplications::class)
        ->callTableAction('approve', $application, ['review_note' => 'Strong channel, clear method.'])
        ->assertHasNoTableActionErrors();

    $application->refresh();

    expect($applicant->fresh()->role)->toBe(UserRole::Creator)
        ->and($applicant->fresh()->isCreator())->toBeTrue()
        ->and($application->status)->toBe(CreatorApplicationStatus::Approved)
        ->and($application->reviewed_by)->toBe($this->admin->id)
        ->and($application->reviewed_at)->not->toBeNull()
        ->and($application->review_note)->toBe('Strong channel, clear method.');
});

test('approving an admin closes the application without taking their role away', function () {
    $applicant = User::factory()->admin()->create();
    $application = CreatorApplication::factory()->create(['user_id' => $applicant->id]);

    Livewire::actingAs($this->admin)
        ->test(ListCreatorApplications::class)
        ->callTableAction('approve', $application, ['review_note' => 'Already runs the place.']);

    expect($applicant->fresh()->role)->toBe(UserRole::Admin)
        ->and($applicant->fresh()->isAdmin())->toBeTrue()
        ->and($application->fresh()->status)->toBe(CreatorApplicationStatus::Approved);
});

test('declining requires a note, because the applicant reads it', function () {
    $applicant = User::factory()->create();
    $application = CreatorApplication::factory()->create(['user_id' => $applicant->id]);

    Livewire::actingAs($this->admin)
        ->test(ListCreatorApplications::class)
        ->callTableAction('decline', $application, ['review_note' => ''])
        ->assertHasTableActionErrors(['review_note']);

    expect($application->fresh()->status)->toBe(CreatorApplicationStatus::Pending);
});

test('declining records the reason and leaves the account where it was', function () {
    $applicant = User::factory()->create();
    $application = CreatorApplication::factory()->create(['user_id' => $applicant->id]);

    Livewire::actingAs($this->admin)
        ->test(ListCreatorApplications::class)
        ->callTableAction('decline', $application, ['review_note' => 'Come back with a few published recipes.'])
        ->assertHasNoTableActionErrors();

    expect($application->fresh()->status)->toBe(CreatorApplicationStatus::Declined)
        ->and($application->fresh()->review_note)->toBe('Come back with a few published recipes.')
        ->and($applicant->fresh()->role)->toBe(UserRole::Member);
});

test('a decided application offers no further decisions', function () {
    $application = CreatorApplication::factory()->approved()->create();

    Livewire::actingAs($this->admin)
        ->test(ListCreatorApplications::class, ['activeTab' => 'approved'])
        ->assertTableActionHidden('approve', $application)
        ->assertTableActionHidden('decline', $application);
});

test('applications can be declined in bulk, skipping the ones already decided', function () {
    $waiting = CreatorApplication::factory()->count(2)->create();
    $already = CreatorApplication::factory()->approved()->create();

    Livewire::actingAs($this->admin)
        ->test(ListCreatorApplications::class, ['activeTab' => 'all'])
        ->callTableBulkAction('declineSelected', $waiting->push($already));

    expect(CreatorApplication::query()->where('status', CreatorApplicationStatus::Declined)->count())->toBe(2)
        ->and($already->fresh()->status)->toBe(CreatorApplicationStatus::Approved)
        ->and($already->fresh()->applicant->role)->toBe(UserRole::Member);
});

test('a declined applicant may apply again', function () {
    $applicant = User::factory()->create();
    CreatorApplication::factory()->declined()->create(['user_id' => $applicant->id]);
    $second = CreatorApplication::factory()->create(['user_id' => $applicant->id]);

    expect($applicant->creatorApplications()->count())->toBe(2)
        ->and($applicant->latestCreatorApplication()->first()?->is($second))->toBeTrue();
});

test('the sidebar badge counts only the applications still waiting', function () {
    CreatorApplication::factory()->count(3)->create();
    CreatorApplication::factory()->approved()->create();

    expect(CreatorApplicationResource::getNavigationBadge())->toBe('3');
});
