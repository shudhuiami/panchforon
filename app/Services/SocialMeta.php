<?php

namespace App\Services;

use App\Models\Page;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * The tags a link preview needs. The storefront renders in the browser, so a
 * crawler that never runs JavaScript would otherwise see the same generic
 * title for every page; this resolves the handful of paths worth describing
 * before the HTML leaves the server.
 *
 * @phpstan-type MetaPayload array{title: string, description: string, url: string, image: string|null, type: string}
 */
class SocialMeta
{
    public function __construct(private readonly SettingsRepository $settings) {}

    /**
     * @return MetaPayload
     */
    public function forPath(string $path): array
    {
        $path = '/'.trim($path, '/');
        $siteName = $this->settings->string('site_name', 'Panchforon');

        if (preg_match('#^/recipes/([a-z0-9\-]+)$#', $path, $matches) === 1) {
            $recipe = Recipe::query()->publiclyVisible()->where('slug', $matches[1])->first();

            if ($recipe !== null) {
                return $this->payload(
                    "{$recipe->title} — {$siteName}",
                    $this->describeRecipe($recipe),
                    $path,
                    $recipe->image_url,
                    'article',
                );
            }
        }

        if (preg_match('#^/p/([a-z0-9\-]+)$#', $path, $matches) === 1) {
            $page = Page::query()->published()->where('slug', $matches[1])->first();

            if ($page !== null) {
                return $this->payload(
                    "{$page->title} — {$siteName}",
                    (string) ($page->meta_description ?: $page->excerpt ?: $this->defaultDescription()),
                    $path,
                    null,
                    'article',
                );
            }
        }

        if (preg_match('#^/cooks/(\d+)$#', $path, $matches) === 1) {
            $cook = User::query()->whereNull('suspended_at')->find((int) $matches[1]);

            if ($cook !== null) {
                return $this->payload(
                    "{$cook->name} — {$siteName}",
                    "Recipes {$cook->name} has shared on {$siteName}.",
                    $path,
                    null,
                    'profile',
                );
            }
        }

        return $this->payload(
            "{$siteName} — recipes worth cooking twice",
            $this->defaultDescription(),
            $path,
            null,
            'website',
        );
    }

    private function describeRecipe(Recipe $recipe): string
    {
        $parts = array_filter([$recipe->cuisine, $recipe->category]);
        $lead = $parts === [] ? 'A community recipe' : implode(' · ', $parts);
        $instructions = Str::of((string) $recipe->instructions)->replaceMatches('/\s+/', ' ')->trim()->limit(140)->value();

        return trim("{$lead}. Serves {$recipe->servings}. {$instructions}");
    }

    private function defaultDescription(): string
    {
        return 'Community recipes with deep South Asian roots, honest ratings, and a meal planner that turns a week of cooking into one merged shopping list.';
    }

    /**
     * @return MetaPayload
     */
    private function payload(string $title, string $description, string $path, ?string $image, string $type): array
    {
        return [
            'title' => Str::limit($title, 90),
            'description' => Str::limit($description, 200),
            'url' => url($path),
            'image' => $image,
            'type' => $type,
        ];
    }
}
