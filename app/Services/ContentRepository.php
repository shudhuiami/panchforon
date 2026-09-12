<?php

namespace App\Services;

use App\Models\ContentBlock;
use App\Models\Page;
use App\Models\PageCacheKeys;
use Illuminate\Support\Facades\Cache;

/**
 * Storefront copy an admin controls. Both lookups are cached and forgotten
 * whenever a page or block is saved, so an edit shows up on the next request
 * without a queue or a deploy.
 */
class ContentRepository
{
    private const TTL_SECONDS = 3600;

    /**
     * The keys the storefront renders, with the wording used when an admin
     * has not overridden them.
     *
     * @var array<string, array{label: string, body: string}>
     */
    public const BLOCKS = [
        'home_hero_eyebrow' => ['label' => 'Home hero badge', 'body' => 'Community recipes, ranked fairly'],
        'home_hero_body' => [
            'label' => 'Home hero paragraph',
            'body' => 'Recipes from home cooks in Bangladesh and far beyond, ranked by the people who cooked them. Plan the week in a tap and shop from one merged list.',
        ],
        'home_cta_title' => ['label' => 'Home call-to-action heading', 'body' => 'Cooked something great? Put it on the table.'],
        'home_cta_body' => [
            'label' => 'Home call-to-action paragraph',
            'body' => 'Post the recipe, get rated by people who actually cooked it, and help someone find their new favourite dinner.',
        ],
        'footer_blurb' => [
            'label' => 'Footer blurb',
            'body' => 'Community recipes with deep South Asian roots, honest ratings, and a meal planner that turns a week of cooking into one merged shopping list.',
        ],
    ];

    /**
     * Every block the storefront may render, defaults filled in.
     *
     * @return array<string, string>
     */
    public function blocks(): array
    {
        return Cache::remember(PageCacheKeys::BLOCKS, self::TTL_SECONDS, function (): array {
            $saved = ContentBlock::query()->pluck('body', 'key')->all();

            $blocks = [];
            foreach (self::BLOCKS as $key => $default) {
                $body = isset($saved[$key]) ? trim((string) $saved[$key]) : '';
                $blocks[$key] = $body !== '' ? $body : $default['body'];
            }

            return $blocks;
        });
    }

    /**
     * Published pages an admin has chosen to list in the footer.
     *
     * @return list<array{slug: string, title: string}>
     */
    public function footerPages(): array
    {
        return Cache::remember(PageCacheKeys::FOOTER, self::TTL_SECONDS, fn (): array => Page::query()
            ->published()
            ->where('show_in_footer', true)
            ->orderBy('position')
            ->orderBy('title')
            ->get(['slug', 'title'])
            ->map(fn (Page $page): array => ['slug' => $page->slug, 'title' => $page->title])
            ->all());
    }

    /**
     * Create any block an admin has not saved yet, so the admin screen lists
     * every editable string rather than only the ones already touched.
     */
    public function seedMissingBlocks(): void
    {
        foreach (self::BLOCKS as $key => $default) {
            ContentBlock::firstOrCreate(['key' => $key], ['label' => $default['label'], 'body' => $default['body']]);
        }
    }
}
