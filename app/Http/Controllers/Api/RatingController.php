<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpsertRatingRequest;
use App\Http\Resources\RatingResource;
use App\Http\Resources\RecipeStatResource;
use App\Models\Rating;
use App\Models\Recipe;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RatingController extends Controller
{
    /**
     * Upsert a rating and review for a recipe by the authenticated user.
     */
    public function upsert(UpsertRatingRequest $request, int $recipeId): JsonResponse
    {
        $recipe = Recipe::findOrFail($recipeId);
        $userId = (int) $request->user()->id;

        $rating = Rating::updateOrCreate(
            [
                'user_id' => $userId,
                'recipe_id' => $recipe->id,
            ],
            [
                'stars' => $request->integer('stars'),
                'review' => $request->input('review'),
            ]
        );

        $rating->load('user');
        $recipe->load('stat');

        return response()->json([
            'rating' => new RatingResource($rating),
            'stat' => new RecipeStatResource($recipe->stat),
        ]);
    }

    /**
     * Remove the authenticated user's rating for a recipe.
     */
    public function destroy(Request $request, int $recipeId): JsonResponse
    {
        $recipe = Recipe::findOrFail($recipeId);
        $userId = (int) $request->user()->id;

        $rating = Rating::where('user_id', $userId)
            ->where('recipe_id', $recipe->id)
            ->first();

        if ($rating) {
            $rating->delete();
        }

        $recipe->load('stat');

        return response()->json([
            'message' => 'Rating removed successfully',
            'stat' => new RecipeStatResource($recipe->stat),
        ]);
    }
}
