<?php

namespace App\Support;

/**
 * Pulls the bare video id out of whatever a creator pasted.
 *
 * What comes out of here ends up in an iframe src, so it is deliberately not a
 * URL: only the eleven characters YouTube allows in an id ever leave this
 * class, and anything that does not reduce to exactly that is null. That keeps
 * the embed unable to point anywhere but youtube.com, whatever was typed.
 */
final class YouTubeVideoId
{
    /** The whole of a video id, and nothing else. */
    private const ID_PATTERN = '/^[A-Za-z0-9_-]{11}$/';

    /** @var list<string> */
    private const HOSTS = ['youtu.be', 'youtube.com', 'youtube-nocookie.com'];

    /** Path prefixes that put the id in the next segment. */
    private const ID_IN_PATH = ['shorts', 'embed', 'v', 'live'];

    /**
     * The id in a pasted link, or null when there is not one.
     *
     * Accepts the four shapes people actually paste — youtu.be/<id>,
     * watch?v=<id>, shorts/<id> and embed/<id> — with or without a scheme, a
     * www., extra query parameters or a trailing slash, and a bare id on its
     * own so a stored value survives a round trip through a form.
     */
    public static function fromInput(string $value): ?string
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        if (preg_match(self::ID_PATTERN, $value) === 1) {
            return $value;
        }

        return self::fromUrl($value);
    }

    private static function fromUrl(string $value): ?string
    {
        /** A pasted link often arrives without its scheme; parse_url needs one. */
        if (! preg_match('#^[a-z][a-z0-9+.-]*://#i', $value)) {
            $value = 'https://'.ltrim($value, '/');
        }

        $parts = parse_url($value);

        if ($parts === false || ! isset($parts['host'])) {
            return null;
        }

        $host = self::canonicalHost($parts['host']);

        if (! in_array($host, self::HOSTS, true)) {
            return null;
        }

        $segments = self::segments($parts['path'] ?? '');

        /** youtu.be puts the id straight after the host and has no other shape. */
        if ($host === 'youtu.be') {
            return self::validate($segments[0] ?? null);
        }

        if (($segments[0] ?? null) === 'watch') {
            return self::validate(self::queryParameter($parts['query'] ?? '', 'v'));
        }

        if (in_array($segments[0] ?? '', self::ID_IN_PATH, true)) {
            return self::validate($segments[1] ?? null);
        }

        return null;
    }

    /**
     * The host without the prefixes that only decide which front end serves
     * the page: www.youtube.com and m.youtube.com are the same site.
     */
    private static function canonicalHost(string $host): string
    {
        $host = mb_strtolower($host);

        foreach (['www.', 'm.', 'music.'] as $prefix) {
            if (str_starts_with($host, $prefix)) {
                return mb_substr($host, mb_strlen($prefix));
            }
        }

        return $host;
    }

    /**
     * @return list<string>
     */
    private static function segments(string $path): array
    {
        return array_values(array_filter(explode('/', $path), fn (string $segment): bool => $segment !== ''));
    }

    private static function queryParameter(string $query, string $name): ?string
    {
        parse_str($query, $parameters);

        $value = $parameters[$name] ?? null;

        return is_string($value) ? $value : null;
    }

    private static function validate(?string $candidate): ?string
    {
        if ($candidate === null) {
            return null;
        }

        $candidate = rawurldecode($candidate);

        return preg_match(self::ID_PATTERN, $candidate) === 1 ? $candidate : null;
    }
}
