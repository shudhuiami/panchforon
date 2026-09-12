<?php

namespace App\Http\Controllers\Api;

use App\Enums\FlagReason;
use App\Enums\FlagStatus;
use App\Http\Controllers\Controller;
use App\Models\ContentFlag;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Reporting a recipe. One open report per person per recipe: reporting the
 * same dish again updates what they said rather than filling the moderation
 * queue with duplicates.
 */
class ContentFlagController extends Controller
{
    public function store(Request $request, int $recipeId): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', Rule::enum(FlagReason::class)],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $recipe = Recipe::query()->publiclyVisible()->findOrFail($recipeId);

        $user = $request->user();
        abort_unless($user instanceof User, 401);

        ContentFlag::updateOrCreate(
            ['recipe_id' => $recipe->id, 'user_id' => $user->id, 'status' => FlagStatus::Open],
            ['reason' => $validated['reason'], 'note' => $validated['note'] ?? null],
        );

        return response()->json([
            'message' => 'Thank you. A moderator will take a look.',
        ], 201);
    }

    /**
     * The reasons the storefront offers, so the list lives in one place.
     */
    public function reasons(): JsonResponse
    {
        return response()->json([
            'data' => collect(FlagReason::cases())
                ->map(fn (FlagReason $reason): array => ['value' => $reason->value, 'label' => $reason->label()])
                ->all(),
        ]);
    }
}
