<?php

namespace App\Http\Controllers\Api;

use App\DTOs\ParsedIngredient;
use App\DTOs\RecipePlanItemInput;
use App\Enums\MealSlot;
use App\Http\Controllers\Controller;
use App\Http\Requests\AddMealPlanItemRequest;
use App\Http\Requests\StoreMealPlanRequest;
use App\Http\Requests\UpdateMealPlanItemRequest;
use App\Http\Requests\UpdateMealPlanRequest;
use App\Http\Resources\MealPlanItemResource;
use App\Http\Resources\MealPlanResource;
use App\Http\Resources\MealPlanSummaryResource;
use App\Http\Resources\ShoppingListItemResource;
use App\Models\MealPlan;
use App\Models\MealPlanItem;
use App\Models\RecipeIngredient;
use App\Models\ShoppingListItem;
use App\Services\IngredientParser;
use App\Services\MergeEngine;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class MealPlanController extends Controller
{
    /**
     * Every plan the cook has built, current one first and the rest newest
     * first behind it.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = $request->integer('per_page', 12);
        $perPage = max(1, min($perPage, 50));

        $plans = MealPlan::query()
            ->where('user_id', $this->userId($request))
            ->with(['items.recipe'])
            ->withCount([
                'items',
                'shoppingListItems',
                'checkedShoppingListItems as shopping_checked_count',
            ])
            ->withSum('items', 'servings')
            ->orderByDesc('is_active')
            ->orderByDesc('starts_on')
            ->orderByDesc('id')
            ->paginate($perPage);

        return MealPlanSummaryResource::collection($plans);
    }

    /**
     * Start a new plan over a chosen stretch of days. It becomes the current
     * one; whatever was current steps aside but is kept.
     */
    public function store(StoreMealPlanRequest $request): JsonResponse
    {
        $startsOn = CarbonImmutable::parse((string) $request->input('starts_on'))->startOfDay();
        $endsOn = CarbonImmutable::parse((string) $request->input('ends_on'))->startOfDay();

        $name = $request->input('name');
        $name = is_string($name) && trim($name) !== ''
            ? trim($name)
            : $this->defaultPlanName($startsOn, $endsOn);

        $plan = MealPlan::create([
            'user_id' => $this->userId($request),
            'name' => $name,
            'starts_on' => $startsOn,
            'ends_on' => $endsOn,
            'is_active' => true,
        ]);

        $plan->activate();

        return $this->planResource($plan)->response()->setStatusCode(201);
    }

    /**
     * Read any one of the cook's own plans, current or long finished.
     */
    public function showPlan(Request $request, int $id): MealPlanResource
    {
        return $this->planResource($this->ownedPlan($request, $id));
    }

    /**
     * Rename or re-date the current plan. Dishes that fall outside the new
     * range are set loose rather than thrown away, so nothing a cook picked
     * disappears because a week got shorter.
     */
    public function updatePlan(UpdateMealPlanRequest $request, int $id): MealPlanResource
    {
        $plan = $this->editablePlan($request, $id);

        $startsOn = $request->has('starts_on')
            ? CarbonImmutable::parse((string) $request->input('starts_on'))->startOfDay()
            : $plan->startDate();

        $endsOn = $request->has('ends_on')
            ? CarbonImmutable::parse((string) $request->input('ends_on'))->startOfDay()
            : $plan->endDate();

        $this->assertRangeIsUsable($startsOn, $endsOn);

        $changes = [
            'starts_on' => $startsOn,
            'ends_on' => $endsOn,
        ];

        $name = $request->input('name');
        if (is_string($name) && trim($name) !== '') {
            $changes['name'] = trim($name);
        }

        $plan->update($changes);

        MealPlanItem::where('meal_plan_id', $plan->id)
            ->whereDate('planned_for', '<', $startsOn->toDateString())
            ->update(['planned_for' => null]);

        MealPlanItem::where('meal_plan_id', $plan->id)
            ->whereDate('planned_for', '>', $endsOn->toDateString())
            ->update(['planned_for' => null]);

        return $this->planResource($plan->refresh());
    }

    /**
     * Throw a plan away with its dishes and its shopping list.
     */
    public function destroyPlan(Request $request, int $id): JsonResponse
    {
        $this->ownedPlan($request, $id)->delete();

        return response()->json([
            'message' => 'Meal plan deleted',
        ]);
    }

    /**
     * Bring an older plan back as the current one, to cook a good week again.
     */
    public function activate(Request $request, int $id): MealPlanResource
    {
        $plan = $this->ownedPlan($request, $id);

        $plan->activate();

        return $this->planResource($plan->refresh());
    }

    /**
     * A past plan's shopping list, exactly as it was left.
     */
    public function planShoppingList(Request $request, int $id): AnonymousResourceCollection
    {
        $plan = $this->ownedPlan($request, $id);

        return ShoppingListItemResource::collection($this->sortedShoppingList($plan));
    }

    /**
     * Get or initialize the active meal plan for authenticated user.
     */
    public function show(Request $request): MealPlanResource
    {
        return $this->planResource($this->activePlan($request));
    }

    /**
     * Add a recipe to active meal plan, on a given day and in a given slot.
     */
    public function addItem(AddMealPlanItemRequest $request): MealPlanResource
    {
        $plan = $this->activePlan($request);

        $plannedFor = $request->filled('planned_for')
            ? CarbonImmutable::parse((string) $request->input('planned_for'))->startOfDay()
            : $plan->defaultPlannedDate();

        $this->assertDayIsInPlan($plan, $plannedFor);

        $mealSlot = $this->mealSlot($request->input('meal_slot')) ?? MealSlot::Dinner;
        $recipeId = $request->integer('recipe_id');
        $servings = $request->integer('servings', 4);

        $existing = MealPlanItem::where('meal_plan_id', $plan->id)
            ->where('recipe_id', $recipeId)
            ->where('meal_slot', $mealSlot)
            ->whereDate('planned_for', $plannedFor->toDateString())
            ->first();

        if ($existing !== null) {
            $existing->update(['servings' => $servings]);
        } else {
            MealPlanItem::create([
                'meal_plan_id' => $plan->id,
                'recipe_id' => $recipeId,
                'planned_for' => $plannedFor,
                'meal_slot' => $mealSlot,
                'servings' => $servings,
            ]);
        }

        return $this->planResource($plan);
    }

    /**
     * Move a dish to another day or slot, or change how many it feeds.
     */
    public function updateItem(UpdateMealPlanItemRequest $request, int $id): MealPlanItemResource
    {
        $item = $this->ownedItem($request, $id);
        $plan = $this->editablePlan($request, (int) $item->meal_plan_id);

        $changes = [];

        if ($request->has('servings')) {
            $changes['servings'] = $request->integer('servings');
        }

        $plannedFor = $item->planned_for !== null
            ? CarbonImmutable::parse($item->planned_for)->startOfDay()
            : null;

        if ($request->has('planned_for')) {
            $plannedFor = $request->filled('planned_for')
                ? CarbonImmutable::parse((string) $request->input('planned_for'))->startOfDay()
                : null;

            if ($plannedFor !== null) {
                $this->assertDayIsInPlan($plan, $plannedFor);
            }

            $changes['planned_for'] = $plannedFor;
        }

        $mealSlot = $item->meal_slot;

        if ($request->has('meal_slot')) {
            $mealSlot = $this->mealSlot($request->input('meal_slot')) ?? $mealSlot;
            $changes['meal_slot'] = $mealSlot;
        }

        $this->assertSlotIsFree($item, $plannedFor, $mealSlot);

        $item->update($changes);

        $item->load(['recipe.stat']);

        return new MealPlanItemResource($item);
    }

    /**
     * Remove an item from the meal plan.
     */
    public function removeItem(Request $request, int $id): JsonResponse
    {
        $item = $this->ownedItem($request, $id);

        $this->editablePlan($request, (int) $item->meal_plan_id);

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
        $plan = $this->activePlan($request);

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
                /**
                 * Water is a real row on a real recipe and it scales with the
                 * dish, but nobody buys it, so it never reaches the list.
                 */
                if ($ri->ingredient !== null && $ri->ingredient->is_shoppable === false) {
                    continue;
                }

                $inputs[] = new RecipePlanItemInput(
                    ingredient: $this->shoppingInput($ri, $parser),
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
                'is_optional' => $line->isOptional,
                'source_note' => $line->sourceNote,
                'is_checked' => $isChecked,
            ]);
        }

        $items = $plan->shoppingListItems()->get();

        return ShoppingListItemResource::collection($items);
    }

    /**
     * One recipe line, as the merge engine wants it.
     *
     * The stored quantity and unit are the truth: a recipe written in the app
     * says what it means, and re-reading the sentence can only lose to it.
     * Rows that came in from an import often carry nothing but that sentence,
     * so those — and only those — are parsed. The unit decides the dimension
     * downstream; the ingredient's default_dimension rides along as the
     * fallback for a line with no usable unit.
     */
    private function shoppingInput(RecipeIngredient $row, IngredientParser $parser): ParsedIngredient
    {
        $ingredient = $row->ingredient;
        $quantity = $row->quantity;
        $unit = $this->cleanUnit($row->unit);
        $parsedName = null;

        if ($quantity === null) {
            $parsed = $parser->parse($row->raw_text);
            $quantity = $parsed->quantity;
            $unit ??= $this->cleanUnit($parsed->unit);
            $parsedName = $parsed->name;
        }

        return new ParsedIngredient(
            quantity: $quantity,
            unit: $unit,
            name: $ingredient !== null ? $ingredient->canonical_name : $parsedName,
            rawText: $row->raw_text,
            ingredientId: $row->ingredient_id,
            dimension: $ingredient?->default_dimension,
            isOptional: $row->is_optional,
        );
    }

    private function cleanUnit(?string $unit): ?string
    {
        $unit = trim((string) $unit);

        return $unit === '' ? null : $unit;
    }

    /**
     * Fetch current shopping list for active meal plan.
     */
    public function getShoppingList(Request $request): AnonymousResourceCollection
    {
        $plan = $this->activePlan($request);

        return ShoppingListItemResource::collection($this->sortedShoppingList($plan));
    }

    /**
     * Toggle or update is_checked state on a shopping list item.
     */
    public function toggleShoppingListItem(Request $request, int $id): ShoppingListItemResource
    {
        $userId = $this->userId($request);

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

    private function userId(Request $request): int
    {
        return (int) $request->user()->id;
    }

    /**
     * The plan the cook is cooking from. A cook who has never made one gets a
     * week starting today, which is what the app has always handed back.
     */
    private function activePlan(Request $request): MealPlan
    {
        $today = CarbonImmutable::today();

        return MealPlan::firstOrCreate(
            ['user_id' => $this->userId($request), 'is_active' => true],
            [
                'name' => 'This week',
                'starts_on' => $today,
                'ends_on' => $today->addDays(MealPlan::DEFAULT_DAYS - 1),
            ]
        );
    }

    /**
     * Someone else's plan is not theirs to know about, so it is missing rather
     * than forbidden.
     */
    private function ownedPlan(Request $request, int $id): MealPlan
    {
        return MealPlan::where('id', $id)
            ->where('user_id', $this->userId($request))
            ->firstOrFail();
    }

    /**
     * Finished plans are a record of what was cooked, so they stay readable
     * but refuse to change.
     */
    private function editablePlan(Request $request, int $id): MealPlan
    {
        $plan = $this->ownedPlan($request, $id);

        if ($plan->is_active !== true) {
            abort(403, 'Only the current plan can be changed.');
        }

        return $plan;
    }

    private function ownedItem(Request $request, int $id): MealPlanItem
    {
        $userId = $this->userId($request);

        return MealPlanItem::where('id', $id)
            ->whereHas('mealPlan', fn ($q) => $q->where('user_id', $userId))
            ->firstOrFail();
    }

    /**
     * @return Collection<int, ShoppingListItem>
     */
    private function sortedShoppingList(MealPlan $plan): Collection
    {
        return $plan->shoppingListItems()
            ->orderBy('is_checked')
            ->orderBy('is_unmerged')
            ->orderBy('id')
            ->get();
    }

    /**
     * The flag is cleared because a resource wrapping a just-created model
     * answers 201, and fetching the plan that the app quietly made for you is
     * still a plain 200. The one endpoint that really creates sets 201 itself.
     */
    private function planResource(MealPlan $plan): MealPlanResource
    {
        $plan->load(['items.recipe.stat'])->loadCount(['items', 'shoppingListItems']);
        $plan->wasRecentlyCreated = false;

        return new MealPlanResource($plan);
    }

    private function mealSlot(mixed $value): ?MealSlot
    {
        return is_string($value) ? MealSlot::tryFrom($value) : null;
    }

    private function defaultPlanName(CarbonImmutable $startsOn, CarbonImmutable $endsOn): string
    {
        $spansAWeek = round($startsOn->diffInDays($endsOn)) + 1 <= MealPlan::DEFAULT_DAYS;

        if ($spansAWeek && $startsOn->toDateString() === CarbonImmutable::today()->toDateString()) {
            return 'This week';
        }

        return 'Plan from '.$startsOn->format('j M Y');
    }

    private function assertRangeIsUsable(CarbonImmutable $startsOn, CarbonImmutable $endsOn): void
    {
        if ($endsOn->toDateString() < $startsOn->toDateString()) {
            throw ValidationException::withMessages([
                'ends_on' => 'The plan has to end on or after it starts.',
            ]);
        }

        if (round($startsOn->diffInDays($endsOn)) + 1 > MealPlan::MAX_DAYS) {
            throw ValidationException::withMessages([
                'ends_on' => 'A plan can cover at most '.MealPlan::MAX_DAYS.' days.',
            ]);
        }
    }

    private function assertDayIsInPlan(MealPlan $plan, CarbonImmutable $day): void
    {
        if (! $plan->covers($day)) {
            throw ValidationException::withMessages([
                'planned_for' => 'That day falls outside this plan.',
            ]);
        }
    }

    /**
     * The same dish twice in one slot on one day is a duplicate, not a plan,
     * and the unique key would refuse it anyway.
     */
    private function assertSlotIsFree(MealPlanItem $item, ?CarbonImmutable $plannedFor, MealSlot $mealSlot): void
    {
        $query = MealPlanItem::where('meal_plan_id', $item->meal_plan_id)
            ->where('recipe_id', $item->recipe_id)
            ->where('meal_slot', $mealSlot->value)
            ->whereKeyNot($item->getKey());

        $query = $plannedFor === null
            ? $query->whereNull('planned_for')
            : $query->whereDate('planned_for', $plannedFor->toDateString());

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'planned_for' => 'That dish is already on the plan for that day.',
            ]);
        }
    }
}
