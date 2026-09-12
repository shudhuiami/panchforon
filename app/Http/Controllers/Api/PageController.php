<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Services\ContentRepository;
use Illuminate\Http\JsonResponse;

/**
 * Admin-written pages, and the storefront copy that goes with them.
 */
class PageController extends Controller
{
    /**
     * The pages an admin has chosen to list in the footer.
     */
    public function index(ContentRepository $content): JsonResponse
    {
        return response()->json(['data' => $content->footerPages()]);
    }

    /**
     * One published page, body already rendered as safe HTML.
     */
    public function show(string $slug): JsonResponse
    {
        $page = Page::query()->published()->where('slug', $slug)->firstOrFail();

        return response()->json([
            'data' => [
                'slug' => $page->slug,
                'title' => $page->title,
                'excerpt' => $page->excerpt,
                'meta_description' => $page->meta_description,
                'html' => $page->renderedBody(),
                'updated_at' => $page->updated_at?->toISOString(),
            ],
        ]);
    }

    /**
     * Every editable storefront string, defaults filled in.
     */
    public function blocks(ContentRepository $content): JsonResponse
    {
        return response()->json(['data' => $content->blocks()]);
    }
}
