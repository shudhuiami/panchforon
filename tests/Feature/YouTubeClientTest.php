<?php

use App\Services\YouTube\YouTubeClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['services.youtube.key' => 'test-key']);
    Cache::flush();

    /** Nothing in this file may reach the real API. */
    Http::preventStrayRequests();
});

function youTube(): YouTubeClient
{
    return app(YouTubeClient::class);
}

/**
 * @return array<string, mixed>
 */
function youTubeQuery(Request $request): array
{
    parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

    return $query;
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function youTubeChannelBody(array $overrides = []): array
{
    return ['items' => [array_replace_recursive([
        'id' => 'UCuAXFkgsw1L7xaCfnd5JJOw',
        'snippet' => [
            'title' => 'Panchforon Kitchen',
            'customUrl' => '@panchforon',
            'thumbnails' => [
                'default' => ['url' => 'https://yt3.test/default.jpg'],
                'high' => ['url' => 'https://yt3.test/high.jpg'],
            ],
        ],
        'contentDetails' => ['relatedPlaylists' => ['uploads' => 'UUuAXFkgsw1L7xaCfnd5JJOw']],
    ], $overrides)]];
}

/**
 * @param  list<array<string, mixed>>  $items
 * @return array<string, mixed>
 */
function youTubeUploadsBody(array $items, ?string $nextPageToken = null): array
{
    $body = ['items' => $items];

    if ($nextPageToken !== null) {
        $body['nextPageToken'] = $nextPageToken;
    }

    return $body;
}

/**
 * @return array<string, mixed>
 */
function youTubeUploadItem(string $videoId, string $title = 'Kosha mangsho'): array
{
    return [
        'snippet' => [
            'title' => $title,
            'publishedAt' => '2026-01-02T10:00:00Z',
            'resourceId' => ['kind' => 'youtube#video', 'videoId' => $videoId],
            'thumbnails' => ['medium' => ['url' => "https://i.ytimg.test/{$videoId}.jpg"]],
        ],
        'contentDetails' => ['videoId' => $videoId, 'videoPublishedAt' => '2026-01-01T09:00:00Z'],
    ];
}

test('a pasted handle resolves through the handle filter', function () {
    Http::fake(['*/youtube/v3/channels*' => Http::response(youTubeChannelBody())]);

    $channel = youTube()->resolveChannel('@panchforon');

    expect($channel?->id)->toBe('UCuAXFkgsw1L7xaCfnd5JJOw')
        ->and($channel?->handle)->toBe('panchforon')
        ->and($channel?->title)->toBe('Panchforon Kitchen')
        ->and($channel?->thumbnailUrl)->toBe('https://yt3.test/high.jpg')
        ->and($channel?->uploadsPlaylistId)->toBe('UUuAXFkgsw1L7xaCfnd5JJOw');

    Http::assertSent(function (Request $request) {
        $query = youTubeQuery($request);

        return str_contains($request->url(), '/youtube/v3/channels')
            && $query['forHandle'] === 'panchforon'
            && $query['part'] === 'snippet,contentDetails'
            && $query['key'] === 'test-key'
            && ! isset($query['id']);
    });
});

/** search.list costs a hundred units. Nothing here may ever call it. */
test('resolving a channel never touches search', function () {
    Http::fake(['*' => Http::response(youTubeChannelBody())]);

    youTube()->resolveChannel('https://www.youtube.com/@panchforon/videos');

    Http::assertNotSent(fn (Request $request) => str_contains($request->url(), '/youtube/v3/search'));
});

test('a channel url resolves through the id filter', function () {
    Http::fake(['*/youtube/v3/channels*' => Http::response(youTubeChannelBody())]);

    expect(youTube()->resolveChannel('https://www.youtube.com/channel/UCuAXFkgsw1L7xaCfnd5JJOw')?->id)
        ->toBe('UCuAXFkgsw1L7xaCfnd5JJOw');

    Http::assertSent(function (Request $request) {
        $query = youTubeQuery($request);

        return $query['id'] === 'UCuAXFkgsw1L7xaCfnd5JJOw' && ! isset($query['forHandle']);
    });
});

test('a legacy user url resolves through the username filter', function () {
    Http::fake(['*/youtube/v3/channels*' => Http::response(youTubeChannelBody())]);

    youTube()->resolveChannel('https://www.youtube.com/user/panchforon');

    Http::assertSent(fn (Request $request) => (youTubeQuery($request)['forUsername'] ?? null) === 'panchforon');
});

test('a channel that answers 404 is simply not a channel', function () {
    Http::fake(['*/youtube/v3/channels*' => Http::response(['error' => ['code' => 404]], 404)]);

    expect(youTube()->resolveChannel('@panchforon'))->toBeNull();

    Http::assertSentCount(1);
});

test('a channel the api does not know is not a channel either', function () {
    Http::fake(['*/youtube/v3/channels*' => Http::response(['items' => []])]);

    expect(youTube()->resolveChannel('@nosuchcook'))->toBeNull();
});

test('a link that could not be a channel never costs a unit', function (string $pasted) {
    Http::fake();

    expect(youTube()->resolveChannel($pasted))->toBeNull();

    Http::assertNothingSent();
})->with([
    'another site' => 'https://vimeo.com/panchforon',
    'a video, not a channel' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
    'a handle too short to be one' => '@a',
    'a channel id that is not one' => 'https://www.youtube.com/channel/NOTACHANNELID',
    'nothing at all' => '   ',
]);

test('uploads come back a page at a time', function () {
    Http::fake([
        '*/youtube/v3/playlistItems*' => Http::sequence()
            ->push(youTubeUploadsBody([
                youTubeUploadItem('dQw4w9WgXcQ', 'Kosha mangsho'),
                youTubeUploadItem('_-aB9cD1efG', 'Shorshe ilish'),
            ], 'PAGE-2'))
            ->push(youTubeUploadsBody([youTubeUploadItem('ABCDEFGHIJK', 'Mishti doi')])),
    ]);

    $first = youTube()->uploads('UUuAXFkgsw1L7xaCfnd5JJOw');

    expect($first->videos)->toHaveCount(2)
        ->and($first->hasMore())->toBeTrue()
        ->and($first->nextPageToken)->toBe('PAGE-2')
        ->and($first->videos[0]->id)->toBe('dQw4w9WgXcQ')
        ->and($first->videos[0]->title)->toBe('Kosha mangsho')
        ->and($first->videos[0]->thumbnailUrl)->toBe('https://i.ytimg.test/dQw4w9WgXcQ.jpg')
        ->and($first->videos[0]->publishedAt?->toDateString())->toBe('2026-01-01');

    $second = youTube()->uploads('UUuAXFkgsw1L7xaCfnd5JJOw', 'PAGE-2');

    expect($second->videos)->toHaveCount(1)
        ->and($second->hasMore())->toBeFalse()
        ->and($second->nextPageToken)->toBeNull();

    Http::assertSent(function (Request $request) {
        $query = youTubeQuery($request);

        return $query['playlistId'] === 'UUuAXFkgsw1L7xaCfnd5JJOw'
            && $query['maxResults'] === '50'
            && $query['part'] === 'snippet,contentDetails'
            && ! isset($query['pageToken']);
    });

    Http::assertSent(fn (Request $request) => (youTubeQuery($request)['pageToken'] ?? null) === 'PAGE-2');
});

test('an upload the api describes oddly is dropped rather than embedded', function () {
    Http::fake(['*/youtube/v3/playlistItems*' => Http::response(youTubeUploadsBody([
        youTubeUploadItem('dQw4w9WgXcQ'),
        ['snippet' => ['title' => 'A deleted video', 'resourceId' => ['videoId' => '']]],
        ['snippet' => ['title' => 'Something else entirely'], 'contentDetails' => ['videoId' => 'https://evil.test/x']],
    ]))]);

    $page = youTube()->uploads('UUuAXFkgsw1L7xaCfnd5JJOw');

    expect($page->videos)->toHaveCount(1)
        ->and($page->videos[0]->id)->toBe('dQw4w9WgXcQ');
});

test('the same channel pasted two ways costs one unit', function () {
    Http::fake(['*/youtube/v3/channels*' => Http::response(youTubeChannelBody())]);

    $first = youTube()->resolveChannel('@panchforon');
    $second = youTube()->resolveChannel('https://www.youtube.com/@Panchforon/videos');

    expect($second?->id)->toBe($first?->id);

    Http::assertSentCount(1);
});

/** Case folds for handles, which YouTube matches case-insensitively — and not for ids, which it does not. */
test('two channel ids differing only in case are two channels', function () {
    Http::fake(['*/youtube/v3/channels*' => Http::response(youTubeChannelBody())]);

    youTube()->resolveChannel('https://www.youtube.com/channel/UCuAXFkgsw1L7xaCfnd5JJOw');
    youTube()->resolveChannel('https://www.youtube.com/channel/UCUaxfKGSW1l7XAcFND5jjOW');

    Http::assertSentCount(2);
});

test('two upload pages differing only in token case are two pages', function () {
    Http::fake(['*/youtube/v3/playlistItems*' => Http::response(youTubeUploadsBody([youTubeUploadItem('dQw4w9WgXcQ')]))]);

    youTube()->uploads('UUuAXFkgsw1L7xaCfnd5JJOw', 'EAAaBlBU');
    youTube()->uploads('UUuAXFkgsw1L7xaCfnd5JJOw', 'eaaabLbu');

    Http::assertSentCount(2);
});

test('a second page view of the same uploads costs nothing', function () {
    Http::fake(['*/youtube/v3/playlistItems*' => Http::response(youTubeUploadsBody([youTubeUploadItem('dQw4w9WgXcQ')]))]);

    youTube()->uploads('UUuAXFkgsw1L7xaCfnd5JJOw');
    youTube()->uploads('UUuAXFkgsw1L7xaCfnd5JJOw');

    Http::assertSentCount(1);

    /** A different page is a different entry, so paging still works. */
    youTube()->uploads('UUuAXFkgsw1L7xaCfnd5JJOw', 'PAGE-2');

    Http::assertSentCount(2);
});

test('a channel that does not exist is remembered, so a typo costs one unit', function () {
    Http::fake(['*/youtube/v3/channels*' => Http::response(['items' => []])]);

    youTube()->resolveChannel('@nosuchcook');
    youTube()->resolveChannel('@nosuchcook');

    Http::assertSentCount(1);
});

/**
 * The regression this codebase already paid for once: a value object in a
 * cache that serialises comes back as __PHP_Incomplete_Class the day the
 * class moves.
 */
test('what goes into the cache is a plain array, never an object', function () {
    Http::fake([
        '*/youtube/v3/channels*' => Http::response(youTubeChannelBody()),
        '*/youtube/v3/playlistItems*' => Http::response(youTubeUploadsBody([youTubeUploadItem('dQw4w9WgXcQ')], 'PAGE-2')),
    ]);

    youTube()->resolveChannel('@panchforon');
    youTube()->uploads('UUuAXFkgsw1L7xaCfnd5JJOw');

    $channelKey = YouTubeClient::channelCacheKey('@panchforon');
    $uploadsKey = YouTubeClient::uploadsCacheKey('UUuAXFkgsw1L7xaCfnd5JJOw');

    expect($channelKey)->toStartWith('youtube.channel.')
        ->and($uploadsKey)->toStartWith('youtube.uploads.')
        ->and(Cache::get($channelKey))->toBeArray()
        ->and(Cache::get($uploadsKey))->toBeArray()
        ->and(serialize(Cache::get($channelKey)))->not->toContain('O:')
        ->and(serialize(Cache::get($uploadsKey)))->not->toContain('O:');
});

test('a call that never reached youtube is not remembered as an empty channel', function () {
    Http::fake(fn () => throw new ConnectionException('Connection timed out'));

    expect(youTube()->resolveChannel('@panchforon'))->toBeNull()
        ->and(Cache::has((string) YouTubeClient::channelCacheKey('@panchforon')))->toBeFalse()
        ->and(youTube()->uploads('UUuAXFkgsw1L7xaCfnd5JJOw')->isEmpty())->toBeTrue()
        ->and(Cache::has(YouTubeClient::uploadsCacheKey('UUuAXFkgsw1L7xaCfnd5JJOw')))->toBeFalse();
});

test('a server error leaves the feature empty rather than broken', function () {
    Http::fake(['*' => Http::response('upstream is unwell', 503)]);

    expect(youTube()->resolveChannel('@panchforon'))->toBeNull()
        ->and(youTube()->uploads('UUuAXFkgsw1L7xaCfnd5JJOw')->isEmpty())->toBeTrue();
});

test('with no key configured the integration does not exist', function () {
    config(['services.youtube.key' => null]);
    Http::fake();

    expect(youTube()->isConfigured())->toBeFalse()
        ->and(youTube()->resolveChannel('@panchforon'))->toBeNull()
        ->and(youTube()->uploads('UUuAXFkgsw1L7xaCfnd5JJOw')->isEmpty())->toBeTrue()
        ->and(youTube()->uploads('UUuAXFkgsw1L7xaCfnd5JJOw')->videos)->toBe([]);

    Http::assertNothingSent();
});

test('an empty key is no key', function () {
    config(['services.youtube.key' => '']);
    Http::fake();

    expect(youTube()->isConfigured())->toBeFalse()
        ->and(youTube()->resolveChannel('@panchforon'))->toBeNull();

    Http::assertNothingSent();
});
