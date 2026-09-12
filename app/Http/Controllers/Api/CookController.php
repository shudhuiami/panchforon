<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CookResource;
use App\Http\Resources\RecipeListResource;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * A cook's public page: who they are and what of theirs the catalogue shows.
 */
class CookController extends Controller
{
    public function show(Request $request, int $id): JsonResponse
    {
        $cook = User::query()
            ->whereNull('suspended_at')
            ->withCount([
                'recipes' => fn ($query) => $query->publiclyVisible(),
                'ratings',
            ])
            ->findOrFail($id);

        $recipes = Recipe::query()
            ->where('user_id', $cook->id)
            ->publiclyVisible()
            ->with('stat')
            ->sorted($request->input('sort', 'bayesian'))
            ->paginate(max(1, min($request->integer('per_page', 12), 50)));

        return response()->json([
            'data' => [
                'cook' => (new CookResource($cook))->resolve(),
                'recipes' => RecipeListResource::collection($recipes)->response()->getData(true),
            ],
        ]);
    }
}
