<?php

namespace App\Services\YouTube;

/**
 * One page of a channel's uploads, plus the token that fetches the next one.
 *
 * A page with no token is the last one; an empty page is also what a caller
 * gets when YouTube is unreachable or switched off, so nothing downstream has
 * to handle a failure separately from an empty channel.
 */
readonly class YouTubeVideoPage
{
    /**
     * @param  list<YouTubeVideo>  $videos
     */
    public function __construct(
        public array $videos = [],
        public ?string $nextPageToken = null,
    ) {}

    public static function empty(): self
    {
        return new self;
    }

    public function isEmpty(): bool
    {
        return $this->videos === [];
    }

    public function hasMore(): bool
    {
        return $this->nextPageToken !== null;
    }

    /**
     * @return array{videos: list<array{id: string, title: string, published_at: ?string, thumbnail_url: ?string}>, next_page_token: ?string}
     */
    public function toArray(): array
    {
        return [
            'videos' => array_map(fn (YouTubeVideo $video): array => $video->toArray(), $this->videos),
            'next_page_token' => $this->nextPageToken,
        ];
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public static function fromArray(array $values): self
    {
        $videos = $values['videos'] ?? [];
        $nextPageToken = $values['next_page_token'] ?? null;

        return new self(
            videos: array_values(array_map(
                fn (array $video): YouTubeVideo => YouTubeVideo::fromArray($video),
                is_array($videos) ? array_filter($videos, is_array(...)) : [],
            )),
            nextPageToken: is_string($nextPageToken) && $nextPageToken !== '' ? $nextPageToken : null,
        );
    }
}
