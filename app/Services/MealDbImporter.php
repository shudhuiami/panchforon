<?php

namespace App\Services;

use App\Enums\RecipeSource;
use App\Models\Ingredient;
use App\Models\IngredientAlias;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MealDbImporter
{
    public function __construct(
        public IngredientParser $parser,
        public RankingService $rankingService,
    ) {}

    /**
     * Import recipes from TheMealDB.
     *
     * @param  array<int, string>  $areas
     * @return int Count of imported recipes
     */
    public function import(int $limit = 300, array $areas = ['Indian', 'Italian', 'Mexican', 'British', 'Chinese', 'American']): int
    {
        $importedCount = 0;
        $unresolvedLogPath = storage_path('logs/unresolved_ingredients.log');

        foreach ($areas as $area) {
            if ($importedCount >= $limit) {
                break;
            }

            $listUrl = 'https://www.themealdb.com/api/json/v1/1/filter.php?a='.urlencode($area);
            $response = Http::timeout(15)->get($listUrl);

            if (! $response->successful()) {
                continue;
            }

            $meals = $response->json('meals') ?? [];

            foreach ($meals as $mealSummary) {
                if ($importedCount >= $limit) {
                    break;
                }

                $mealId = $mealSummary['idMeal'] ?? null;
                if (! $mealId) {
                    continue;
                }

                $detailUrl = 'https://www.themealdb.com/api/json/v1/1/lookup.php?i='.urlencode($mealId);
                $detailResponse = Http::timeout(15)->get($detailUrl);

                if (! $detailResponse->successful()) {
                    continue;
                }

                $detailMeals = $detailResponse->json('meals');
                if (empty($detailMeals) || ! isset($detailMeals[0])) {
                    continue;
                }

                $mealData = $detailMeals[0];
                $this->saveRecipe($mealData, $unresolvedLogPath);
                $importedCount++;
            }
        }

        return $importedCount;
    }

    /**
     * Save or update a recipe from raw MealDB payload.
     *
     * @param  array<string, mixed>  $data
     */
    public function saveRecipe(array $data, ?string $unresolvedLogPath = null): Recipe
    {
        $externalId = (string) $data['idMeal'];
        $title = trim((string) $data['strMeal']);
        $slug = Str::slug($title);

        // Ensure slug is unique if conflict exists with another ID
        $existing = Recipe::where('slug', $slug)->where('external_id', '!=', $externalId)->first();
        if ($existing) {
            $slug .= '-'.$externalId;
        }

        $recipe = Recipe::updateOrCreate(
            ['external_id' => $externalId],
            [
                'user_id' => null,
                'source' => RecipeSource::Api,
                'title' => $title,
                'slug' => $slug,
                'cuisine' => ! empty($data['strArea']) ? trim((string) $data['strArea']) : null,
                'category' => ! empty($data['strCategory']) ? trim((string) $data['strCategory']) : null,
                'instructions' => trim((string) ($data['strInstructions'] ?? '')),
                'image_url' => ! empty($data['strMealThumb']) ? trim((string) $data['strMealThumb']) : null,
                'servings' => 4,
                'source_url' => ! empty($data['strSource']) ? trim((string) $data['strSource']) : 'https://www.themealdb.com',
            ]
        );

        // Remove existing ingredients to allow clean re-import
        $recipe->ingredients()->delete();

        // Parse ingredients from 20 numbered pairs
        $position = 0;
        for ($i = 1; $i <= 20; $i++) {
            $rawIng = isset($data["strIngredient{$i}"]) ? trim((string) $data["strIngredient{$i}"]) : '';
            $rawMeasure = isset($data["strMeasure{$i}"]) ? trim((string) $data["strMeasure{$i}"]) : '';

            if ($rawIng === '' && $rawMeasure === '') {
                continue;
            }

            if ($rawIng === '') {
                continue;
            }

            $rawCombined = trim("{$rawMeasure} {$rawIng}");
            $parsed = $this->parser->parse($rawCombined, function (string $name) use ($unresolvedLogPath, $rawCombined) {
                return $this->resolveOrCreateIngredient($name, $unresolvedLogPath, $rawCombined);
            });

            RecipeIngredient::create([
                'recipe_id' => $recipe->id,
                'ingredient_id' => $parsed->ingredientId,
                'quantity' => $parsed->quantity,
                'unit' => $parsed->unit,
                'raw_text' => $rawCombined,
                'position' => $position++,
            ]);
        }

        // Initialize default stats
        $this->rankingService->updateRecipeStats($recipe->id);

        return $recipe;
    }

    /**
     * Resolve ingredient against canonical names and aliases, or create new canonical record.
     */
    protected function resolveOrCreateIngredient(string $name, ?string $logPath, string $rawText): ?int
    {
        $normalized = mb_strtolower(trim($name));
        if ($normalized === '') {
            return null;
        }

        // 1. Check canonical_name
        $canonical = Ingredient::where('canonical_name', $normalized)->first();
        if ($canonical) {
            return $canonical->id;
        }

        // 2. Check ingredient_aliases
        $alias = IngredientAlias::where('alias', $normalized)->first();
        if ($alias) {
            return $alias->ingredient_id;
        }

        // 3. Create new canonical record
        $created = Ingredient::create([
            'canonical_name' => $normalized,
            'default_dimension' => 'none',
        ]);

        // Log if unparseable or new
        if ($logPath) {
            $line = date('Y-m-d H:i:s')." | Raw: [{$rawText}] -> Created canonical: [{$normalized}]\n";
            File::append($logPath, $line);
        }

        return $created->id;
    }
}
