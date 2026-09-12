<?php

namespace App\Models;

use Illuminate\Support\Facades\Cache;

/**
 * The cache entries that go stale when a page or content block changes.
 */
final class PageCacheKeys
{
    public const FOOTER = 'api.pages.footer';

    public const BLOCKS = 'api.content-blocks';

    public static function forget(): void
    {
        Cache::forget(self::FOOTER);
        Cache::forget(self::BLOCKS);
    }
}
