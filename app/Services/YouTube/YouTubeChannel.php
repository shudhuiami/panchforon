<?php

namespace App\Services\YouTube;

/**
 * A creator's channel, as much of it as linking one to an account needs.
 *
 * The uploads playlist is the useful half: it is the only cheap way to list a
 * channel's videos, since searching for them costs a hundred times as much.
 */
readonly class YouTubeChannel
{
    public function __construct(
        public string $id,
        public ?string $handle,
        public string $title,
        public ?string $thumbnailUrl,
        public string $uploadsPlaylistId,
    ) {}

    /**
     * The plain form this is cached as. A value object in a cache that
     * serialises comes back as an incomplete class the day it is renamed,
     * rather than as an error anyone can act on.
     *
     * @return array{id: string, handle: ?string, title: string, thumbnail_url: ?string, uploads_playlist_id: string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'handle' => $this->handle,
            'title' => $this->title,
            'thumbnail_url' => $this->thumbnailUrl,
            'uploads_playlist_id' => $this->uploadsPlaylistId,
        ];
    }

    /**
     * Deliberately forgiving about what it is handed: this reads back a cache
     * entry that may have been written by an older deploy.
     *
     * @param  array<string, mixed>  $values
     */
    public static function fromArray(array $values): self
    {
        return new self(
            id: (string) ($values['id'] ?? ''),
            handle: isset($values['handle']) ? (string) $values['handle'] : null,
            title: (string) ($values['title'] ?? ''),
            thumbnailUrl: isset($values['thumbnail_url']) ? (string) $values['thumbnail_url'] : null,
            uploadsPlaylistId: (string) ($values['uploads_playlist_id'] ?? ''),
        );
    }
}
