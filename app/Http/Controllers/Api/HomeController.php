<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\HomeFeed;
use Illuminate\Http\JsonResponse;

/**
 * The storefront home page in one request: stats, the featured recipe, the
 * top-rated and newest recipes, and the cuisines with a cover photo each.
 */
class HomeController extends Controller
{
    public function __invoke(HomeFeed $feed): JsonResponse
    {
        return response()->json(['data' => $feed->cached()]);
    }
}
