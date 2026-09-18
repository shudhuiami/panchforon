<?php

use App\Filament\Studio\Pages\Channel;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * Connecting a channel to an account.
 *
 * Nothing in this file may reach Google: every test fakes the endpoint it
 * expects and preventStrayRequests() turns anything else into a failure rather
 * than a slow test that quietly went online.
 *
 * The admin panel is the default one, so each test says which panel it means
 * before a studio page will resolve its own routes.
 */
beforeEach(function () {
    Filament::setCurrentPanel('studio');

    config(['services.youtube.key' => 'test-key']);
    Cache::flush();

    Http::preventStrayRequests();
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function studioChannelBody(array $overrides = []): array
{
    return ['items' => [array_replace_recursive([
        'id' => 'UCuAXFkgsw1L7xaCfnd5JJOw',
        'snippet' => [
            'title' => 'Panchforon Kitchen',
            'customUrl' => '@panchforon',
            'thumbnails' => ['high' => ['url' => 'https://yt3.test/high.jpg']],
        ],
        'contentDetails' => ['relatedPlaylists' => ['uploads' => 'UUuAXFkgsw1L7xaCfnd5JJOw']],
    ], $overrides)]];
}

test('connecting a channel stores its id, handle and title', function () {
    Http::fake(['*/youtube/v3/channels*' => Http::response(studioChannelBody())]);

    $creator = User::factory()->creator()->create();
    $this->actingAs($creator);

    Livewire::test(Channel::class)
        ->fillForm(['channel' => 'https://www.youtube.com/@panchforon'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($creator->refresh())
        ->youtube_channel_id->toBe('UCuAXFkgsw1L7xaCfnd5JJOw')
        ->youtube_channel_handle->toBe('panchforon')
        ->youtube_channel_title->toBe('Panchforon Kitchen');
});

test('a handle that resolves to nothing reports back and stores nothing', function () {
    Http::fake(['*/youtube/v3/channels*' => Http::response(['items' => []])]);

    $creator = User::factory()->creator()->create();
    $this->actingAs($creator);

    Livewire::test(Channel::class)
        ->fillForm(['channel' => '@nosuchcook'])
        ->call('save')
        ->assertNotified('No channel found');

    expect($creator->refresh())
        ->youtube_channel_id->toBeNull()
        ->youtube_channel_handle->toBeNull()
        ->youtube_channel_title->toBeNull();
});

test('a channel the api cannot be reached for stores nothing either', function () {
    Http::fake(['*/youtube/v3/channels*' => Http::response(['error' => ['code' => 403]], 403)]);

    $creator = User::factory()->creator()->create();
    $this->actingAs($creator);

    Livewire::test(Channel::class)
        ->fillForm(['channel' => '@panchforon'])
        ->call('save')
        ->assertNotified('No channel found');

    expect($creator->refresh()->youtube_channel_id)->toBeNull();
});

test('disconnecting clears all three columns', function () {
    $creator = User::factory()->creator()->create([
        'youtube_channel_id' => 'UCuAXFkgsw1L7xaCfnd5JJOw',
        'youtube_channel_handle' => 'panchforon',
        'youtube_channel_title' => 'Panchforon Kitchen',
    ]);

    /** The connected panel asks for the channel picture while the page renders. */
    Http::fake(['*/youtube/v3/channels*' => Http::response(studioChannelBody())]);

    $this->actingAs($creator);

    Livewire::test(Channel::class)
        ->call('disconnect')
        ->assertNotified('Channel disconnected');

    expect($creator->refresh())
        ->youtube_channel_id->toBeNull()
        ->youtube_channel_handle->toBeNull()
        ->youtube_channel_title->toBeNull();
});

test('a member who is not a creator cannot reach the page', function () {
    $member = User::factory()->create();

    expect(Channel::canAccess())->toBeFalse();

    /**
     * A member is refused the studio outright, so the 403 here is the panel's.
     * canAccess() above is the page's own answer, which is what keeps the page
     * shut if the panel gate is ever widened.
     */
    $this->actingAs($member)->get('/studio/channel')->assertForbidden();
});

test('a suspended creator cannot reach the page', function () {
    $suspended = User::factory()->creator()->suspended()->create();

    $this->actingAs($suspended);

    expect(Channel::canAccess())->toBeFalse();
});

test('a creator can reach the page', function () {
    $creator = User::factory()->creator()->create();

    $this->actingAs($creator)->get('/studio/channel')->assertSuccessful();
});

test('with no api key the page says so and offers nothing to fill in', function () {
    config(['services.youtube.key' => null]);

    $creator = User::factory()->creator()->create();
    $this->actingAs($creator);

    Livewire::test(Channel::class)
        ->assertSuccessful()
        ->assertSee('YouTube is not switched on for this site')
        ->assertDontSee('Channel address or @handle');

    /** Not one call was made, because there was nothing to call with. */
    Http::assertNothingSent();
});

test('the page never claims a connected channel has been verified', function () {
    $creator = User::factory()->creator()->create();
    $this->actingAs($creator);

    Livewire::test(Channel::class)
        ->assertSee('does not prove it is yours');
});
