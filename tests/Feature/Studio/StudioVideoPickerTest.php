<?php

use App\Filament\Studio\Resources\Recipes\Pages\CreateRecipe;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * Choosing a video from your own channel while writing a recipe.
 *
 * Nothing here may reach Google: every test fakes what it expects and
 * preventStrayRequests() turns anything else into a failure rather than a slow
 * test that quietly went online.
 */
beforeEach(function () {
    Filament::setCurrentPanel('studio');

    config(['services.youtube.key' => 'test-key']);
    Cache::flush();

    Http::preventStrayRequests();
});

function pickerAction(): TestAction
{
    return TestAction::make('pickYouTubeVideo')->schemaComponent('youtube_video_id');
}

function connectedCreator(): User
{
    return User::factory()->creator()->create([
        'youtube_channel_id' => 'UCuAXFkgsw1L7xaCfnd5JJOw',
        'youtube_channel_handle' => 'panchforon',
        'youtube_channel_title' => 'Panchforon Kitchen',
    ]);
}

/**
 * @return array<string, mixed>
 */
function pickerChannelBody(): array
{
    return ['items' => [[
        'id' => 'UCuAXFkgsw1L7xaCfnd5JJOw',
        'snippet' => ['title' => 'Panchforon Kitchen', 'customUrl' => '@panchforon'],
        'contentDetails' => ['relatedPlaylists' => ['uploads' => 'UUuAXFkgsw1L7xaCfnd5JJOw']],
    ]]];
}

/**
 * @return array<string, mixed>
 */
function pickerUploadItem(string $videoId, string $title, string $publishedAt = '2026-01-02T10:00:00Z'): array
{
    return [
        'snippet' => [
            'title' => $title,
            'publishedAt' => $publishedAt,
            'resourceId' => ['kind' => 'youtube#video', 'videoId' => $videoId],
            'thumbnails' => ['medium' => ['url' => "https://i.ytimg.test/{$videoId}.jpg"]],
        ],
        'contentDetails' => ['videoId' => $videoId, 'videoPublishedAt' => $publishedAt],
    ];
}

function fakeUploads(): void
{
    Http::fake([
        '*/youtube/v3/channels*' => Http::response(pickerChannelBody()),
        '*/youtube/v3/playlistItems*' => function (Request $request) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return ($query['pageToken'] ?? null) === 'PAGE2'
                ? Http::response(['items' => [pickerUploadItem('bbbbbbbbbbb', 'Bhapa Doi', '2025-11-20T08:00:00Z')]])
                : Http::response([
                    'items' => [pickerUploadItem('aaaaaaaaaaa', 'Kosha Mangsho')],
                    'nextPageToken' => 'PAGE2',
                ]);
        },
    ]);
}

test('the picker lists the connected channel\'s uploads', function () {
    fakeUploads();

    $this->actingAs(connectedCreator());

    Livewire::test(CreateRecipe::class)
        ->mountAction(pickerAction())
        ->assertMountedActionModalSee('Kosha Mangsho')
        ->assertMountedActionModalSee('2 Jan 2026')
        ->assertMountedActionModalSeeHtml('https://i.ytimg.test/aaaaaaaaaaa.jpg');
});

test('choosing a video puts its id in the field', function () {
    fakeUploads();

    $this->actingAs(connectedCreator());

    Livewire::test(CreateRecipe::class)
        ->callAction(pickerAction(), ['video_id' => 'aaaaaaaaaaa'])
        ->assertHasNoActionErrors()
        ->assertFormSet(['youtube_video_id' => 'aaaaaaaaaaa']);
});

test('the older pages are walked with the token the client handed back', function () {
    fakeUploads();

    $this->actingAs(connectedCreator());

    Livewire::test(CreateRecipe::class)
        ->mountAction(pickerAction())
        ->assertMountedActionModalSee('Kosha Mangsho')
        /** The second page is not fetched until it is asked for. */
        ->assertMountedActionModalDontSee('Bhapa Doi')
        ->callAction(TestAction::make('loadMore')->schemaComponent('olderVideos'))
        ->assertMountedActionModalSee('Kosha Mangsho')
        ->assertMountedActionModalSee('Bhapa Doi');

    Http::assertSent(function (Request $request) {
        parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

        return str_contains($request->url(), '/youtube/v3/playlistItems')
            && ($query['pageToken'] ?? null) === 'PAGE2';
    });
});

test('search is never called, whatever the picker does', function () {
    fakeUploads();

    $this->actingAs(connectedCreator());

    Livewire::test(CreateRecipe::class)->mountAction(pickerAction());

    Http::assertNotSent(fn (Request $request) => str_contains($request->url(), '/youtube/v3/search'));
});

test('with no channel connected the picker points at the channel page', function () {
    Http::fake();

    $this->actingAs(User::factory()->creator()->create());

    Livewire::test(CreateRecipe::class)
        ->mountAction(pickerAction())
        ->assertMountedActionModalSee('No channel connected yet')
        ->assertMountedActionModalSeeHtml('/studio/channel');

    /** Nothing to list, so nothing was asked for. */
    Http::assertNothingSent();
});

test('a connected channel with nothing on it says so rather than looking broken', function () {
    Http::fake([
        '*/youtube/v3/channels*' => Http::response(pickerChannelBody()),
        '*/youtube/v3/playlistItems*' => Http::response(['items' => []]),
    ]);

    $this->actingAs(connectedCreator());

    Livewire::test(CreateRecipe::class)
        ->mountAction(pickerAction())
        ->assertMountedActionModalSee('This channel has no public videos yet');
});

test('with no api key there is no picker at all', function () {
    config(['services.youtube.key' => null]);

    $this->actingAs(connectedCreator());

    Livewire::test(CreateRecipe::class)
        ->assertActionDoesNotExist(pickerAction());

    Http::assertNothingSent();
});

test('a picked video survives the save and reaches the column', function () {
    fakeUploads();

    $creator = connectedCreator();
    $this->actingAs($creator);

    Livewire::test(CreateRecipe::class)
        ->fillForm([
            'title' => 'Kosha Mangsho',
            'instructions' => 'Brown the onions properly. Then keep going.',
            'servings' => 4,
            'ingredients' => [['name' => 'mutton', 'quantity' => 1, 'unit' => 'kg']],
        ])
        ->callAction(pickerAction(), ['video_id' => 'aaaaaaaaaaa'])
        ->call('create')
        ->assertHasNoFormErrors();

    expect($creator->recipes()->firstWhere('title', 'Kosha Mangsho')?->youtube_video_id)
        ->toBe('aaaaaaaaaaa');
});
