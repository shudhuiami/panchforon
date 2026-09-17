<?php

namespace App\Services\YouTube;

use Carbon\CarbonImmutable;

/**
 * One public upload, as the picker shows it.
 *
 * The id is always the bare eleven characters — never a URL — because it is
 * what the embed is built from.
 */
readonly class YouTubeVideo
{
    public function __construct(
        public string $id,
        public string $title,
        public ?CarbonImmutable $publishedAt,
        public ?string $thumbnailUrl,
    ) {}

    /**
     * @return array{id: string, title: string, published_at: ?string, thumbnail_url: ?string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'published_at' => $this->publishedAt?->toIso8601String(),
            'thumbnail_url' => $this->thumbnailUrl,
        ];
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public static function fromArray(array $values): self
    {
        $publishedAt = $values['published_at'] ?? null;

        return new self(
            id: (string) ($values['id'] ?? ''),
            title: (string) ($values['title'] ?? ''),
            publishedAt: is_string($publishedAt) && $publishedAt !== '' ? CarbonImmutable::parse($publishedAt) : null,
            thumbnailUrl: isset($values['thumbnail_url']) ? (string) $values['thumbnail_url'] : null,
        );
    }
}
