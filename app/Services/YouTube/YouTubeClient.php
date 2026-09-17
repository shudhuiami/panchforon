<?php

namespace App\Services\YouTube;

use App\Support\YouTubeVideoId;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Reads a creator's public channel and its uploads.
 *
 * An API key is all this needs, because none of it touches a private account:
 * with no key configured a channel simply never resolves, in the same way an
 * unconfigured social provider does not exist.
 *
 * The quota is the thing to design around. A day is ten thousand units,
 * channels.list and playlistItems.list cost one each, and search.list costs a
 * hundred — which is why the uploads playlist is what lists a channel's videos
 * and search is never called at all. Both calls are cached for an hour on top
 * of that, so a creator reloading the picker costs nothing.
 */
class YouTubeClient
{
    private const ENDPOINT = 'https://www.googleapis.com/youtube/v3/';

    /** Long enough to absorb a reload, short enough that a new upload appears. */
    private const TTL_SECONDS = 3600;

    /** The most playlistItems.list will return for the one unit it costs. */
    private const PAGE_SIZE = 50;

    private const TIMEOUT_SECONDS = 10;

    /** A handle is 3–30 of these, with or without the leading @. */
    private const HANDLE_PATTERN = '/^[A-Za-z0-9._-]{3,30}$/';

    private const CHANNEL_ID_PATTERN = '/^UC[A-Za-z0-9_-]{22}$/';

    public function __construct(private readonly Config $config) {}

    /**
     * The channel behind a pasted handle or URL, or null when there is not one.
     *
     * channels.list takes exactly one filter, so what was pasted decides which:
     * /channel/UC… is an id, /user/name is a legacy username, and everything
     * else — @handle, /c/name, a bare name — is looked up as a handle, which is
     * what YouTube turned the old custom URLs into.
     */
    public function resolveChannel(string $handleOrUrl): ?YouTubeChannel
    {
        $filter = self::channelFilterFor($handleOrUrl);

        if ($filter === null || ! $this->isConfigured()) {
            return null;
        }

        [$name, $value] = $filter;

        $payload = $this->remember(
            self::cacheKey('channel', $name, $value),
            fn (): ?array => $this->fetchChannel($name, $value),
        );

        return $payload === [] ? null : YouTubeChannel::fromArray($payload);
    }

    /**
     * One page of a channel's uploads, newest first, fifty at a time.
     *
     * Pass the nextPageToken of the page before to walk the rest; an empty page
     * is the end of the list, an unreachable API and a missing key alike.
     */
    public function uploads(string $uploadsPlaylistId, ?string $pageToken = null): YouTubeVideoPage
    {
        if (trim($uploadsPlaylistId) === '' || ! $this->isConfigured()) {
            return YouTubeVideoPage::empty();
        }

        $payload = $this->remember(
            self::uploadsCacheKey($uploadsPlaylistId, $pageToken),
            fn (): ?array => $this->fetchUploads($uploadsPlaylistId, $pageToken),
        );

        return YouTubeVideoPage::fromArray($payload);
    }

    /**
     * Where a resolved channel is cached, for anything that needs to drop it.
     * Null when the input was never going to resolve.
     */
    public static function channelCacheKey(string $handleOrUrl): ?string
    {
        $filter = self::channelFilterFor($handleOrUrl);

        return $filter === null ? null : self::cacheKey('channel', $filter[0], $filter[1]);
    }

    public static function uploadsCacheKey(string $uploadsPlaylistId, ?string $pageToken = null): string
    {
        return self::cacheKey('uploads', 'page', $uploadsPlaylistId.'|'.$pageToken);
    }

    public function isConfigured(): bool
    {
        return $this->apiKey() !== null;
    }

    /**
     * Which channels.list filter a pasted value calls for, and the value to
     * pass it. Junk is rejected here rather than at YouTube, so a mistyped
     * handle never costs a unit.
     *
     * @return array{0: string, 1: string}|null
     */
    private static function channelFilterFor(string $input): ?array
    {
        $input = trim($input);

        if ($input === '') {
            return null;
        }

        return str_contains($input, '/')
            ? self::channelFilterFromUrl($input)
            : self::channelFilterFromName($input);
    }

    /**
     * @return array{0: string, 1: string}|null
     */
    private static function channelFilterFromUrl(string $url): ?array
    {
        if (! preg_match('#^[a-z][a-z0-9+.-]*://#i', $url)) {
            $url = 'https://'.ltrim($url, '/');
        }

        $parts = parse_url($url);

        if ($parts === false || ! isset($parts['host'])) {
            return null;
        }

        $host = mb_strtolower($parts['host']);
        $host = preg_replace('/^(www|m|music)\./', '', $host) ?? $host;

        if ($host !== 'youtube.com') {
            return null;
        }

        $segments = array_values(array_filter(
            explode('/', $parts['path'] ?? ''),
            fn (string $segment): bool => $segment !== '',
        ));

        $first = $segments[0] ?? '';

        if ($first === 'channel') {
            $id = $segments[1] ?? '';

            return preg_match(self::CHANNEL_ID_PATTERN, $id) === 1 ? ['id', $id] : null;
        }

        if ($first === 'user') {
            return self::handleFilter($segments[1] ?? '', 'forUsername');
        }

        /**
         * /c/name is the old custom URL. There is no filter for it any more,
         * and the handle YouTube minted from it is the closest thing left.
         */
        if ($first === 'c') {
            return self::handleFilter($segments[1] ?? '', 'forHandle');
        }

        return str_starts_with($first, '@') ? self::channelFilterFromName($first) : null;
    }

    /**
     * @return array{0: string, 1: string}|null
     */
    private static function channelFilterFromName(string $name): ?array
    {
        if (preg_match(self::CHANNEL_ID_PATTERN, $name) === 1) {
            return ['id', $name];
        }

        return self::handleFilter($name, 'forHandle');
    }

    /**
     * A handle is matched case-insensitively at YouTube's end, so it is folded
     * here: @Panchforon and @panchforon are then one lookup and one cache
     * entry rather than two. A channel id is left exactly as it was — those are
     * base64url, where case is part of the identity.
     *
     * @return array{0: string, 1: string}|null
     */
    private static function handleFilter(string $value, string $filter): ?array
    {
        $value = ltrim(rawurldecode($value), '@');

        return preg_match(self::HANDLE_PATTERN, $value) === 1 ? [$filter, mb_strtolower($value)] : null;
    }

    /**
     * @return array<string, mixed>|null Null when the call never reached YouTube.
     */
    private function fetchChannel(string $filter, string $value): ?array
    {
        $body = $this->get('channels', ['part' => 'snippet,contentDetails', $filter => $value]);

        if ($body === null) {
            return null;
        }

        $items = $body['items'] ?? null;
        $item = is_array($items) ? ($items[0] ?? null) : null;

        if (! is_array($item)) {
            return [];
        }

        $id = $item['id'] ?? null;
        $uploads = $item['contentDetails']['relatedPlaylists']['uploads'] ?? null;

        if (! is_string($id) || $id === '' || ! is_string($uploads) || $uploads === '') {
            return [];
        }

        $snippet = is_array($item['snippet'] ?? null) ? $item['snippet'] : [];
        $customUrl = $snippet['customUrl'] ?? null;
        $title = $snippet['title'] ?? null;

        return [
            'id' => $id,
            'handle' => is_string($customUrl) && $customUrl !== '' ? ltrim($customUrl, '@') : null,
            'title' => is_string($title) ? $title : '',
            'thumbnail_url' => $this->bestThumbnail($snippet['thumbnails'] ?? null),
            'uploads_playlist_id' => $uploads,
        ];
    }

    /**
     * @return array<string, mixed>|null Null when the call never reached YouTube.
     */
    private function fetchUploads(string $playlistId, ?string $pageToken): ?array
    {
        $query = [
            'part' => 'snippet,contentDetails',
            'playlistId' => $playlistId,
            'maxResults' => self::PAGE_SIZE,
        ];

        if ($pageToken !== null && $pageToken !== '') {
            $query['pageToken'] = $pageToken;
        }

        $body = $this->get('playlistItems', $query);

        if ($body === null) {
            return null;
        }

        $items = $body['items'] ?? null;
        $videos = [];

        foreach (is_array($items) ? $items : [] as $item) {
            $video = is_array($item) ? $this->videoFrom($item) : null;

            if ($video !== null) {
                $videos[] = $video;
            }
        }

        $nextPageToken = $body['nextPageToken'] ?? null;

        return [
            'videos' => $videos,
            'next_page_token' => is_string($nextPageToken) && $nextPageToken !== '' ? $nextPageToken : null,
        ];
    }

    /**
     * @param  array<mixed>  $item
     * @return array{id: string, title: string, published_at: ?string, thumbnail_url: ?string}|null
     */
    private function videoFrom(array $item): ?array
    {
        $snippet = is_array($item['snippet'] ?? null) ? $item['snippet'] : [];
        $contentDetails = is_array($item['contentDetails'] ?? null) ? $item['contentDetails'] : [];

        $rawId = $contentDetails['videoId'] ?? ($snippet['resourceId']['videoId'] ?? null);

        /** Even YouTube's own answer goes through the extractor: only a bare id may reach an embed. */
        $id = is_string($rawId) ? YouTubeVideoId::fromInput($rawId) : null;

        if ($id === null) {
            return null;
        }

        /** When the video went public, rather than when it was added to the playlist. */
        $publishedAt = $contentDetails['videoPublishedAt'] ?? ($snippet['publishedAt'] ?? null);
        $title = $snippet['title'] ?? null;

        return [
            'id' => $id,
            'title' => is_string($title) ? $title : '',
            'published_at' => is_string($publishedAt) && $publishedAt !== '' ? $publishedAt : null,
            'thumbnail_url' => $this->bestThumbnail($snippet['thumbnails'] ?? null),
        ];
    }

    /**
     * The largest thumbnail on offer, falling back down the sizes YouTube
     * always provides.
     */
    private function bestThumbnail(mixed $thumbnails): ?string
    {
        if (! is_array($thumbnails)) {
            return null;
        }

        foreach (['maxres', 'standard', 'high', 'medium', 'default'] as $size) {
            $url = $thumbnails[$size]['url'] ?? null;

            if (is_string($url) && $url !== '') {
                return $url;
            }
        }

        return null;
    }

    /**
     * @param  array<string, scalar>  $query
     * @return array<string, mixed>|null Null when the call never reached YouTube.
     */
    private function get(string $resource, array $query): ?array
    {
        $key = $this->apiKey();

        if ($key === null) {
            return null;
        }

        try {
            $response = Http::timeout(self::TIMEOUT_SECONDS)
                ->acceptJson()
                ->get(self::ENDPOINT.$resource, [...$query, 'key' => $key]);
        } catch (ConnectionException) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $body = $response->json();

        return is_array($body) ? $body : [];
    }

    private function apiKey(): ?string
    {
        $key = $this->config->get('services.youtube.key');

        return is_string($key) && $key !== '' ? $key : null;
    }

    /**
     * Hashed because a handle, a playlist id and a page token are all free text
     * as far as this class is concerned, and a cache key may not be. The value
     * goes in with its case intact: ids and page tokens are base64url, so
     * folding them here would make two different playlists one cache entry.
     */
    private static function cacheKey(string $kind, string $scope, string $value): string
    {
        return "youtube.{$kind}.{$scope}.".sha1($value);
    }

    /**
     * Cache a payload for an hour, but only when the call actually reached
     * YouTube. A definitive "no such channel" is worth remembering — a mistyped
     * handle reloaded fifty times must not cost fifty units — while a timeout
     * is not, or one bad minute would switch the feature off for the rest of
     * the hour.
     *
     * Plain arrays go in and come out. A value object in a cache that
     * serialises comes back as an incomplete class the day it moves.
     *
     * @param  callable(): (array<string, mixed>|null)  $fetch
     * @return array<string, mixed>
     */
    private function remember(string $key, callable $fetch): array
    {
        $reached = true;

        $payload = Cache::remember($key, self::TTL_SECONDS, function () use ($fetch, &$reached): array {
            $result = $fetch();
            $reached = $result !== null;

            return $result ?? [];
        });

        if (! $reached) {
            Cache::forget($key);
        }

        return $payload;
    }
}
