<?php

namespace App\Http\Controllers\Api;

use App\DTOs\RecipePlanItemInput;
use App\Http\Controllers\Controller;
use App\Http\Requests\AddMealPlanItemRequest;
use App\Http\Requests\UpdateMealPlanItemRequest;
use App\Http\Resources\MealPlanItemResource;
use App\Http\Resources\MealPlanResource;
use App\Http\Resources\ShoppingListItemResource;
use App\Models\MealPlan;
use App\Models\MealPlanItem;
use App\Models\ShoppingListItem;
use App\Services\IngredientParser;
use App\Services\MergeEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MealPlanController extends Controller
{
    /**
     * Get or initialize the active meal plan for authenticated user.
     */
    public function show(Request $request): MealPlanResource
    {
        $userId = (int) $request->user()->id;

        $plan = MealPlan::firstOrCreate(
            ['user_id' => $userId, 'is_active' => true],
            ['name' => 'This week']
        );

        $plan->load(['items.recipe.stat']);
        $plan->wasRecentlyCreated = false;

        return new MealPlanResource($plan);
    }

    /**
     * Add a recipe to active meal plan.
     */
    public function addItem(AddMealPlanItemRequest $request): MealPlanResource
    {
        $userId = (int) $request->user()->id;

        $plan = MealPlan::firstOrCreate(
            ['user_id' => $userId, 'is_active' => true],
            ['name' => 'This week']
        );

        MealPlanItem::updateOrCreate(
            [
                'meal_plan_id' => $plan->id,
                'recipe_id' => $request->integer('recipe_id'),
            ],
            [
                'servings' => $request->integer('servings', 4),
            ]
        );

        $plan->load(['items.recipe.stat']);
        $plan->wasRecentlyCreated = false;

        return new MealPlanResource($plan);
    }

    /**
     * Change servings for a meal plan item.
     */
    public function updateItem(UpdateMealPlanItemRequest $request, int $id): MealPlanItemResource
    {
        $userId = (int) $request->user()->id;

        $item = MealPlanItem::where('id', $id)
            ->whereHas('mealPlan', fn ($q) => $q->where('user_id', $userId))
            ->firstOrFail();

        $item->update([
            'servings' => $request->integer('servings'),
        ]);

        $item->load(['recipe.stat']);

        return new MealPlanItemResource($item);
    }

    /**
     * Remove an item from the meal plan.
     */
    public function removeItem(Request $request, int $id): JsonResponse
    {
        $userId = (int) $request->user()->id;

        $item = MealPlanItem::where('id', $id)
            ->whereHas('mealPlan', fn ($q) => $q->where('user_id', $userId))
            ->firstOrFail();

        $item->delete();

        return response()->json([
            'message' => 'Recipe removed from meal plan',
        ]);
    }

    /**
     * Generate merged shopping list from active meal plan (idempotent, preserves check state).
     */
    public function generateShoppingList(
        Request $request,
        MergeEngine $mergeEngine,
        IngredientParser $parser,
    ): AnonymousResourceCollection {
        $userId = (int) $request->user()->id;

        $plan = MealPlan::firstOrCreate(
            ['user_id' => $userId, 'is_active' => true],
            ['name' => 'This week']
        );

        $plan->load(['items.recipe.ingredients.ingredient', 'shoppingListItems']);

        // Remember existing checked state
        $checkedMap = [];
        foreach ($plan->shoppingListItems as $existingItem) {
            if ($existingItem->is_checked) {
                $key = ($existingItem->ingredient_id ?? 'null').'::'.mb_strtolower(trim($existingItem->display_name)).'::'.($existingItem->unit ?? '');
                $checkedMap[$key] = true;
            }
        }

        /** @var array<int, RecipePlanItemInput> $inputs */
        $inputs = [];

        foreach ($plan->items as $planItem) {
            $recipe = $planItem->recipe;
            if (! $recipe) {
                continue;
            }

            $multiplier = (float) ($planItem->servings / ($recipe->servings ?: 4));

            foreach ($recipe->ingredients as $ri) {
                $parsed = $parser->parse($ri->raw_text);

                if ($ri->ingredient_id) {
                    $parsed = $parsed->withIngredientId($ri->ingredient_id);
                }
                if ($ri->ingredient !== null) {
                    $parsed = $parsed->withDimension($ri->ingredient->default_dimension);
                }

                $inputs[] = new RecipePlanItemInput(
                    ingredient: $parsed,
                    servingsMultiplier: $multiplier,
                    recipeTitle: $recipe->title,
                );
            }
        }

        $mergedLines = $mergeEngine->merge($inputs);

        // Replace persisted lines while preserving checked status
        $plan->shoppingListItems()->delete();

        foreach ($mergedLines as $line) {
            $checkKey = ($line->ingredientId ?? 'null').'::'.mb_strtolower(trim($line->displayName)).'::'.($line->unit ?? '');
            $isChecked = isset($checkedMap[$checkKey]);

            ShoppingListItem::create([
                'meal_plan_id' => $plan->id,
                'ingredient_id' => $line->ingredientId,
                'display_name' => $line->displayName,
                'quantity' => $line->quantity,
                'unit' => $line->unit,
                'is_unmerged' => $line->isUnmerged,
                'source_note' => $line->sourceNote,
                'is_checked' => $isChecked,
            ]);
        }

        $items = $plan->shoppingListItems()->get();

        return ShoppingListItemResource::collection($items);
    }

    /**
     * Fetch current shopping list for active meal plan.
     */
    public function getShoppingList(Request $request): AnonymousResourceCollection
    {
        $userId = (int) $request->user()->id;

        $plan = MealPlan::firstOrCreate(
            ['user_id' => $userId, 'is_active' => true],
            ['name' => 'This week']
        );

        $items = $plan->shoppingListItems()
            ->orderBy('is_checked')
            ->orderBy('is_unmerged')
            ->orderBy('id')
            ->get();

        return ShoppingListItemResource::collection($items);
    }

    /**
     * Toggle or update is_checked state on a shopping list item.
     */
    public function toggleShoppingListItem(Request $request, int $id): ShoppingListItemResource
    {
        $userId = (int) $request->user()->id;

        $item = ShoppingListItem::where('id', $id)
            ->whereHas('mealPlan', fn ($q) => $q->where('user_id', $userId))
            ->firstOrFail();

        $newState = $request->has('is_checked')
            ? $request->boolean('is_checked')
            : ! $item->is_checked;

        $item->update([
            'is_checked' => $newState,
        ]);

        return new ShoppingListItemResource($item);
    }
}
