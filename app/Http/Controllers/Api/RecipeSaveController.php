<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RecipeListResource;
use App\Models\Recipe;
use App\Models\RecipeSave;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Saving a recipe for later. Saving is idempotent, so the heart can be tapped
 * twice without erroring, and a save never survives the recipe leaving public
 * view: the list only returns recipes the catalogue would still show.
 */
class RecipeSaveController extends Controller
{
    /**
     * The signed-in cook's saved recipes, newest save first.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = max(1, min($request->integer('per_page', 12), 50));

        $recipes = $this->user($request)
            ->savedRecipes()
            ->publiclyVisible()
            ->with('stat')
            ->paginate($perPage);

        return RecipeListResource::collection($recipes);
    }

    /**
     * Keep a recipe for later.
     */
    public function store(Request $request, int $recipeId): JsonResponse
    {
        $recipe = Recipe::query()->publiclyVisible()->findOrFail($recipeId);

        RecipeSave::firstOrCreate([
            'user_id' => $this->user($request)->id,
            'recipe_id' => $recipe->id,
        ]);

        return response()->json(['data' => ['recipe_id' => $recipe->id, 'is_saved' => true]], 201);
    }

    /**
     * Forget a saved recipe.
     */
    public function destroy(Request $request, int $recipeId): JsonResponse
    {
        RecipeSave::query()
            ->where('user_id', $this->user($request)->id)
            ->where('recipe_id', $recipeId)
            ->delete();

        return response()->json(['data' => ['recipe_id' => $recipeId, 'is_saved' => false]]);
    }

    /**
     * Every recipe id this cook has saved, so the client can mark hearts in
     * one request rather than one per card.
     */
    public function ids(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->user($request)->recipeSaves()->pluck('recipe_id')->all(),
        ]);
    }

    private function user(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return $user;
    }
}
