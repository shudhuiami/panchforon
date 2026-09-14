<?php

namespace Database\Seeders;

use App\DTOs\ParsedIngredient;
use App\DTOs\RecipePlanItemInput;
use App\Enums\MealSlot;
use App\Enums\RecipeSource;
use App\Models\Ingredient;
use App\Models\MealPlan;
use App\Models\MealPlanItem;
use App\Models\Rating;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\ShoppingListItem;
use App\Models\User;
use App\Services\MergeEngine;
use App\Services\RankingService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * The catalogue, the demo accounts, and one week of planned meals.
 *
 * The recipes themselves live in database/data/bangladeshi-recipes.json, where
 * every amount is already a number and a unit: "500–650 ml" became 575 ml when
 * the file was written, not when the seeder runs. Nothing here parses a
 * sentence, because nothing here has to.
 *
 * Re-running is safe. Recipes key on their slug, ingredient rows on their
 * position within a recipe, and the demo plan is rebuilt from scratch, so a
 * second run neither duplicates a row nor leaves an edited one behind.
 *
 * @phpstan-type RecipeSeedIngredient array{ingredient: string, quantity: float|int|null, unit: ?string, is_optional: bool, note: ?string, raw_text: string}
 * @phpstan-type RecipeSeedRow array{slug: string, title: string, name_bn: ?string, cuisine: string, category: string, servings: int, prep_minutes: ?int, cook_minutes: ?int, spice_level: ?string, image_url: ?string, instructions: string, ingredients: array<int, RecipeSeedIngredient>}
 */
class RecipeSeeder extends Seeder
{
    private const DATA_FILE = 'data/bangladeshi-recipes.json';

    private const DEMO_PLAN_NAME = 'This week';

    /**
     * The thin demo recipes the trial pack replaces. Their slugs differ from
     * the pack's, so they have to be named to be cleared out; the chicken
     * roast keeps its slug and is simply overwritten with the real one.
     */
    private const SUPERSEDED_SLUGS = [
        'smoky-begun-bharta',
        'panch-phoron-masoor-dal-tadka',
        'chittagong-beef-kala-bhuna',
    ];

    /**
     * What the demo cook has planned for the first three days: a curry, a dal
     * and a bhorta, which is both a real Bangladeshi dinner and a shopping
     * list with something to merge.
     */
    private const DEMO_PLAN_SLUGS = [
        'bangladeshi-chicken-curry',
        'masoor-dal',
        'aloo-bhorta',
    ];

    public function run(RankingService $rankingService, MergeEngine $mergeEngine): void
    {
        $demoUser = User::firstOrCreate(
            ['email' => 'demo@panchforon.com'],
            [
                'name' => 'Ahmed Zobayer',
                'password' => Hash::make('password'),
            ]
        );

        $reviewerUser = User::firstOrCreate(
            ['email' => 'reviewer@panchforon.com'],
            [
                'name' => 'Recipe Reviewer',
                'password' => Hash::make('password'),
            ]
        );

        Recipe::query()->whereIn('slug', self::SUPERSEDED_SLUGS)->delete();

        /** @var array<string, int> $ingredientIds */
        $ingredientIds = Ingredient::query()->pluck('id', 'canonical_name')->all();

        $ratings = $this->demoRatings($demoUser, $reviewerUser);

        /** @var array<string, Recipe> $recipes */
        $recipes = [];

        foreach ([...$this->packRecipes(), ...$this->legacyRecipes()] as $data) {
            $recipe = $this->upsertRecipe($data, $demoUser, $ingredientIds);

            foreach ($ratings[$data['slug']] ?? [] as $rating) {
                Rating::firstOrCreate(
                    ['user_id' => $rating['user']->id, 'recipe_id' => $recipe->id],
                    ['stars' => $rating['stars'], 'review' => $rating['review']]
                );
            }

            $rankingService->updateRecipeStats($recipe->id);

            $recipes[$data['slug']] = $recipe;
        }

        $this->seedDemoPlan($demoUser, $recipes, $mergeEngine);
    }

    /**
     * One recipe and its ingredient rows, written in place.
     *
     * @param  RecipeSeedRow  $data
     * @param  array<string, int>  $ingredientIds
     */
    private function upsertRecipe(array $data, User $author, array $ingredientIds): Recipe
    {
        $recipe = Recipe::updateOrCreate(
            ['slug' => $data['slug']],
            [
                'user_id' => $author->id,
                'source' => RecipeSource::User,
                'title' => $data['title'],
                'name_bn' => $data['name_bn'],
                'cuisine' => $data['cuisine'],
                'category' => $data['category'],
                'instructions' => $data['instructions'],
                'image_url' => $data['image_url'],
                'servings' => $data['servings'],
                'prep_minutes' => $data['prep_minutes'],
                'cook_minutes' => $data['cook_minutes'],
                'spice_level' => $data['spice_level'],
                'source_url' => null,
            ]
        );

        $position = 0;

        foreach ($data['ingredients'] as $row) {
            $canonical = $row['ingredient'];

            if (! isset($ingredientIds[$canonical])) {
                throw new RuntimeException(
                    "Recipe {$data['slug']} wants the ingredient \"{$canonical}\", which the ingredient dictionary does not have. Add it to IngredientSeeder, or point the recipe at the canonical name it should use."
                );
            }

            RecipeIngredient::updateOrCreate(
                ['recipe_id' => $recipe->id, 'position' => $position],
                [
                    'ingredient_id' => $ingredientIds[$canonical],
                    'quantity' => $row['quantity'],
                    'unit' => $row['unit'],
                    'is_optional' => $row['is_optional'],
                    'note' => $row['note'],
                    'raw_text' => $row['raw_text'],
                ]
            );

            $position++;
        }

        RecipeIngredient::query()
            ->where('recipe_id', $recipe->id)
            ->where('position', '>=', $position)
            ->delete();

        return $recipe;
    }

    /**
     * The demo cook's active week, rebuilt from scratch each run so a second
     * seeding cannot leave two of anything behind.
     *
     * @param  array<string, Recipe>  $recipes
     */
    private function seedDemoPlan(User $demoUser, array $recipes, MergeEngine $mergeEngine): void
    {
        $planStart = CarbonImmutable::today();

        $plan = MealPlan::updateOrCreate(
            ['user_id' => $demoUser->id, 'name' => self::DEMO_PLAN_NAME],
            [
                'starts_on' => $planStart,
                'ends_on' => $planStart->addDays(MealPlan::DEFAULT_DAYS - 1),
                'is_active' => true,
            ]
        );

        $plan->items()->delete();
        $plan->shoppingListItems()->delete();

        /** @var array<int, RecipePlanItemInput> $planInputs */
        $planInputs = [];

        foreach (self::DEMO_PLAN_SLUGS as $dayOffset => $slug) {
            $recipe = $recipes[$slug] ?? null;

            if ($recipe === null) {
                continue;
            }

            MealPlanItem::create([
                'meal_plan_id' => $plan->id,
                'recipe_id' => $recipe->id,
                'servings' => 4,
                'planned_for' => $planStart->addDays($dayOffset),
                'meal_slot' => MealSlot::Dinner,
            ]);

            $multiplier = 4 / ($recipe->servings ?: 4);

            $recipe->load('ingredients.ingredient');

            foreach ($recipe->ingredients as $row) {
                /**
                 * Water is a real row on a real recipe — the method needs it
                 * and it scales with the dish — but nobody buys it, so it
                 * never reaches the list.
                 */
                if ($row->ingredient !== null && ! $row->ingredient->is_shoppable) {
                    continue;
                }

                $planInputs[] = new RecipePlanItemInput(
                    ingredient: new ParsedIngredient(
                        quantity: $row->quantity,
                        unit: $row->unit,
                        name: $row->ingredient?->canonical_name,
                        rawText: $row->raw_text,
                        ingredientId: $row->ingredient_id,
                        dimension: $row->ingredient?->default_dimension,
                        isOptional: $row->is_optional,
                    ),
                    servingsMultiplier: $multiplier,
                    recipeTitle: $recipe->title,
                );
            }
        }

        foreach ($mergeEngine->merge($planInputs) as $line) {
            ShoppingListItem::create([
                'meal_plan_id' => $plan->id,
                'ingredient_id' => $line->ingredientId,
                'display_name' => $line->displayName,
                'quantity' => $line->quantity,
                'unit' => $line->unit,
                'is_unmerged' => $line->isUnmerged,
                'is_optional' => $line->isOptional,
                'source_note' => $line->sourceNote,
                'is_checked' => false,
            ]);
        }
    }

    /**
     * The trial pack: ten dishes standardised for four adults, with every
     * range already resolved to a single number.
     *
     * @return array<int, RecipeSeedRow>
     */
    private function packRecipes(): array
    {
        $path = database_path(self::DATA_FILE);
        $json = file_get_contents($path);

        if ($json === false) {
            throw new RuntimeException("Could not read the recipe data file at {$path}.");
        }

        /** @var array<int, RecipeSeedRow> $decoded */
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        return $decoded;
    }

    /**
     * Shorshe ilish predates the pack and is not in it, so it is kept here by
     * hand. Its amounts are deliberately left in spoons: the catalogue should
     * hold at least one recipe whose units have to be converted before they
     * can be added up.
     *
     * @return array<int, RecipeSeedRow>
     */
    private function legacyRecipes(): array
    {
        return [
            [
                'slug' => 'traditional-shorshe-ilish',
                'title' => 'Traditional Shorshe Ilish',
                'name_bn' => 'সরষে ইলিশ',
                'cuisine' => 'Bangladeshi',
                'category' => 'Seafood',
                'servings' => 4,
                'prep_minutes' => 15,
                'cook_minutes' => 20,
                'spice_level' => 'hot',
                'image_url' => 'https://images.unsplash.com/photo-1534422298391-e4f8c172dddb?auto=format&fit=crop&w=800&q=80',
                'instructions' => "1. Wash and pat dry Ilish steaks. Rub with turmeric and salt.\n2. Grind yellow and black mustard seeds with green chilies and a pinch of salt into a fine paste.\n3. Heat mustard oil until smoking, lower heat and temper with kalonji.\n4. Add mustard paste, turmeric, and slit green chilies with 1/2 cup warm water.\n5. Gently place the fish steaks into the bubbling gravy. Cook covered for 10 minutes.",
                'ingredients' => [
                    ['ingredient' => 'ilish', 'quantity' => 500, 'unit' => 'g', 'is_optional' => false, 'note' => '4 steaks', 'raw_text' => '500 g ilish fish steaks'],
                    ['ingredient' => 'mustard oil', 'quantity' => 3, 'unit' => 'tbsp', 'is_optional' => false, 'note' => 'Heated until it smokes', 'raw_text' => '3 tbsp mustard oil'],
                    ['ingredient' => 'turmeric', 'quantity' => 1, 'unit' => 'tsp', 'is_optional' => false, 'note' => null, 'raw_text' => '1 tsp turmeric powder'],
                    ['ingredient' => 'chili', 'quantity' => 4, 'unit' => 'piece', 'is_optional' => false, 'note' => 'Slit', 'raw_text' => '4 green chilies, slit'],
                    ['ingredient' => 'nigella', 'quantity' => 1, 'unit' => 'g', 'is_optional' => true, 'note' => 'Kalonji, for the tempering', 'raw_text' => '1 g nigella seed'],
                    ['ingredient' => 'salt', 'quantity' => null, 'unit' => null, 'is_optional' => false, 'note' => 'To taste', 'raw_text' => 'salt to taste'],
                ],
            ],
        ];
    }

    /**
     * @return array<string, array<int, array{user: User, stars: int, review: string}>>
     */
    private function demoRatings(User $demoUser, User $reviewerUser): array
    {
        return [
            'bangladeshi-chicken-curry' => [
                ['user' => $reviewerUser, 'stars' => 5, 'review' => 'The weeknight jhol I grew up on. Frying the potatoes first is what makes it.'],
                ['user' => $demoUser, 'stars' => 5, 'review' => 'Scales honestly for guests, and the timings are the real ones.'],
            ],
            'beef-bhuna' => [
                ['user' => $reviewerUser, 'stars' => 5, 'review' => 'Cooked down until the masala clings to the meat. Worth every one of those minutes.'],
            ],
            'rui-machher-jhol' => [
                ['user' => $reviewerUser, 'stars' => 4, 'review' => 'Light, clean gravy. The kalojira is only optional on paper.'],
            ],
            'masoor-dal' => [
                ['user' => $reviewerUser, 'stars' => 5, 'review' => 'Pure comfort food for any Bengali dinner table.'],
            ],
            'aloo-bhorta' => [
                ['user' => $demoUser, 'stars' => 4, 'review' => 'Rustic and fiery, and ready before the rice is.'],
            ],
            'begun-bhorta' => [
                ['user' => $demoUser, 'stars' => 4, 'review' => 'Char the eggplant properly over the flame and it nearly makes itself.'],
            ],
            'bhuna-khichuri' => [
                ['user' => $reviewerUser, 'stars' => 5, 'review' => 'Rainy-day food. One pot, and nothing left over.'],
            ],
            'bangladeshi-polao' => [
                ['user' => $reviewerUser, 'stars' => 5, 'review' => 'Fluffy and fragrant, and it does not fight the roast for attention.'],
            ],
            'bangladeshi-chicken-roast' => [
                ['user' => $reviewerUser, 'stars' => 5, 'review' => 'Absolute wedding-style Biye Bari roast flavor! Perfectly balanced sweetness.'],
                ['user' => $demoUser, 'stars' => 5, 'review' => 'A family staple recipe that never fails.'],
            ],
            'dudh-semai' => [
                ['user' => $demoUser, 'stars' => 5, 'review' => 'Eid morning in a bowl. Take it off the heat looser than you think.'],
            ],
            'traditional-shorshe-ilish' => [
                ['user' => $reviewerUser, 'stars' => 5, 'review' => 'Pungent, authentic, and truly delicious with steaming hot rice.'],
            ],
        ];
    }
}
