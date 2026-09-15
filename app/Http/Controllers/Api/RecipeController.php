<?php

namespace App\Http\Controllers\Api;

use App\Enums\ModerationStatus;
use App\Enums\RecipeSource;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRecipeRequest;
use App\Http\Requests\UpdateRecipeRequest;
use App\Http\Resources\RecipeDetailResource;
use App\Http\Resources\RecipeListResource;
use App\Models\Ingredient;
use App\Models\IngredientAlias;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\User;
use App\Services\IngredientParser;
use App\Services\RankingService;
use App\Services\SettingsRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class RecipeController extends Controller
{
    /**
     * List recipes with filters, sorting, and pagination.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = $request->integer('per_page', 12);
        $perPage = max(1, min($perPage, 50));

        $query = Recipe::query()->with('stat')->publiclyVisible();

        $query->filter([
            'cuisine' => $request->input('cuisine'),
            'category' => $request->input('category'),
            'q' => $request->input('q'),
        ]);

        $query->sorted($request->input('sort', 'bayesian'));

        $recipes = $query->paginate($perPage);

        return RecipeListResource::collection($recipes);
    }

    /**
     * Show recipe detail by slug.
     */
    public function show(Request $request, string $slug): RecipeDetailResource
    {
        $recipe = Recipe::where('slug', $slug)
            ->with(['user', 'ingredients.ingredient', 'stat', 'ratings.user'])
            ->firstOrFail();

        if (! $this->canViewRecipe($request, $recipe)) {
            abort(404);
        }

        return new RecipeDetailResource($recipe);
    }

    /**
     * Store a new recipe created by authenticated user.
     */
    public function store(
        StoreRecipeRequest $request,
        IngredientParser $parser,
        RankingService $rankingService,
    ): JsonResponse {
        $userId = (int) $request->user()->id;
        $title = (string) $request->title;
        $slug = Str::slug($title);

        $count = Recipe::where('slug', 'like', "{$slug}%")->count();
        if ($count > 0) {
            $slug .= '-'.($count + 1);
        }

        $recipe = Recipe::create([
            'user_id' => $userId,
            'source' => RecipeSource::User,
            'moderation_status' => $request->moderationStatus(),
            'title' => $title,
            'name_bn' => $request->input('name_bn'),
            'slug' => $slug,
            'cuisine' => $request->cuisine,
            'category' => $request->category,
            'instructions' => $request->instructions,
            'image_url' => $request->image_url,
            'servings' => $request->integer('servings', 4),
            'prep_minutes' => $request->input('prep_minutes'),
            'cook_minutes' => $request->input('cook_minutes'),
            'spice_level' => $request->input('spice_level'),
            'source_url' => $request->source_url,
        ]);

        $this->writeIngredients($recipe, (array) $request->input('ingredients', []), $parser);

        // Initialize recipe stat
        $rankingService->updateRecipeStats($recipe->id);

        $recipe->load(['user', 'ingredients.ingredient', 'stat', 'ratings.user']);

        return (new RecipeDetailResource($recipe))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update an existing recipe (owner only).
     *
     * The moderation status is left alone unless the recipe is a draft and the
     * author asked for it to be published, so editing a live recipe can never
     * pull it off the site.
     */
    public function update(
        UpdateRecipeRequest $request,
        int $id,
        IngredientParser $parser,
    ): RecipeDetailResource {
        $recipe = Recipe::findOrFail($id);

        if ((int) $recipe->user_id !== (int) $request->user()->id) {
            abort(403, 'You are not authorized to update this recipe.');
        }

        $data = $request->safe()->only([
            'name_bn',
            'cuisine',
            'category',
            'instructions',
            'image_url',
            'servings',
            'prep_minutes',
            'cook_minutes',
            'spice_level',
            'source_url',
        ]);

        if ($recipe->isDraft() && $request->publishesDraft()) {
            $this->assertSubmissionsOpen();
            $data['moderation_status'] = ModerationStatus::Pending;
        }

        if ($request->has('title')) {
            $newTitle = (string) $request->title;
            if ($newTitle !== $recipe->title) {
                $slug = Str::slug($newTitle);
                $existing = Recipe::where('slug', $slug)->where('id', '!=', $recipe->id)->first();
                if ($existing) {
                    $slug .= '-'.$recipe->id;
                }
                $data['title'] = $newTitle;
                $data['slug'] = $slug;
            }
        }

        $recipe->update($data);

        if ($request->has('ingredients')) {
            $recipe->ingredients()->delete();
            $this->writeIngredients($recipe, (array) $request->input('ingredients', []), $parser);
        }

        $recipe->load(['user', 'ingredients.ingredient', 'stat', 'ratings.user']);

        return new RecipeDetailResource($recipe);
    }

    /**
     * Write a recipe's ingredient rows, in the order they were sent.
     *
     * A row can arrive structured (name, quantity, unit) or as one line of free
     * text; the parser fills in whatever was not sent, and anything the client
     * stated explicitly wins over what was parsed out of the text. is_optional
     * and note belong to the row rather than to the ingredient, so they are
     * taken verbatim and never inferred.
     *
     * @param  array<int|string, mixed>  $rows
     */
    protected function writeIngredients(Recipe $recipe, array $rows, IngredientParser $parser): void
    {
        $position = 0;

        foreach ($rows as $ingItem) {
            $rawText = ! empty($ingItem['raw_text']) ? trim((string) $ingItem['raw_text']) : '';
            $customName = ! empty($ingItem['name']) ? trim((string) $ingItem['name']) : '';
            $qty = isset($ingItem['quantity']) && $ingItem['quantity'] !== '' ? (float) $ingItem['quantity'] : null;
            $unit = ! empty($ingItem['unit']) ? trim((string) $ingItem['unit']) : null;
            $note = ! empty($ingItem['note']) ? trim((string) $ingItem['note']) : null;

            if ($rawText === '' && $customName !== '') {
                $rawText = trim(($qty !== null ? "{$qty} " : '').($unit ? "{$unit} " : '').$customName);
            }

            $parsed = $parser->parse($rawText !== '' ? $rawText : $customName, function (string $name) {
                return $this->resolveOrCreateIngredient($name);
            });

            RecipeIngredient::create([
                'recipe_id' => $recipe->id,
                'ingredient_id' => $parsed->ingredientId,
                'quantity' => $qty ?? $parsed->quantity,
                'unit' => $unit ?? $parsed->unit,
                'is_optional' => ! empty($ingItem['is_optional']),
                'note' => $note,
                'raw_text' => $rawText !== '' ? $rawText : ($customName ?: 'Ingredient'),
                'position' => $position++,
            ]);
        }
    }

    /**
     * Submit a draft for review (owner only). It becomes a normal pending
     * submission from here; a moderator publishes it exactly as they would any
     * other. Anything that is not a draft is already past this point, so it is
     * a 422 rather than a silent success.
     */
    public function publish(Request $request, int $id): RecipeDetailResource
    {
        $recipe = Recipe::findOrFail($id);

        if ((int) $recipe->user_id !== (int) $request->user()->id) {
            abort(403, 'You are not authorized to publish this recipe.');
        }

        if (! $recipe->isDraft()) {
            abort(422, 'Only a draft can be published.');
        }

        $this->assertSubmissionsOpen();

        $recipe->update(['moderation_status' => ModerationStatus::Pending]);

        $recipe->load(['user', 'ingredients.ingredient', 'stat', 'ratings.user']);

        return new RecipeDetailResource($recipe);
    }

    /**
     * Delete an existing recipe (owner only).
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $recipe = Recipe::findOrFail($id);

        if ((int) $recipe->user_id !== (int) $request->user()->id) {
            abort(403, 'You are not authorized to delete this recipe.');
        }

        $recipe->delete();

        return response()->json([
            'message' => 'Recipe deleted successfully',
        ]);
    }

    /**
     * List distinct cuisines with counts.
     */
    public function cuisines(): JsonResponse
    {
        $cuisines = Recipe::query()
            ->publiclyVisible()
            ->selectRaw('cuisine, count(*) as count')
            ->whereNotNull('cuisine')
            ->where('cuisine', '!=', '')
            ->groupBy('cuisine')
            ->orderBy('count', 'desc')
            ->get();

        return response()->json([
            'data' => $cuisines,
        ]);
    }

    /**
     * List distinct categories with counts.
     */
    public function categories(): JsonResponse
    {
        $categories = Recipe::query()
            ->publiclyVisible()
            ->selectRaw('category, count(*) as count')
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->groupBy('category')
            ->orderBy('count', 'desc')
            ->get();

        return response()->json([
            'data' => $categories,
        ]);
    }

    /**
     * Joining the review queue is a submission, so it honours the same
     * "submissions open" toggle the store endpoint does. Keeping a private
     * draft never does, which stops the toggle being sidestepped by saving a
     * draft first and publishing it a moment later.
     */
    protected function assertSubmissionsOpen(): void
    {
        abort_unless(
            app(SettingsRepository::class)->boolean('submissions_open', true),
            403,
            'Recipe submissions are currently closed.',
        );
    }

    /**
     * A recipe that is not approved — a draft, a submission awaiting review or
     * one an admin pulled — stays readable by its author and by admins, so
     * submitting one does not look like it silently vanished. Everyone else
     * gets a 404 rather than a 403, which would confirm that the recipe exists.
     */
    protected function canViewRecipe(Request $request, Recipe $recipe): bool
    {
        if ($recipe->moderation_status === ModerationStatus::Approved) {
            return true;
        }

        $user = $request->user('sanctum');

        if (! $user instanceof User) {
            return false;
        }

        return $user->is_admin || (int) $recipe->user_id === (int) $user->id;
    }

    /**
     * Resolve ingredient name or create canonical record.
     */
    protected function resolveOrCreateIngredient(string $name): ?int
    {
        $normalized = mb_strtolower(trim($name));
        if ($normalized === '') {
            return null;
        }

        $canonical = Ingredient::where('canonical_name', $normalized)->first();
        if ($canonical) {
            return $canonical->id;
        }

        $alias = IngredientAlias::where('alias', $normalized)->first();
        if ($alias) {
            return $alias->ingredient_id;
        }

        $created = Ingredient::create([
            'canonical_name' => $normalized,
            'default_dimension' => 'none',
        ]);

        return $created->id;
    }
}
