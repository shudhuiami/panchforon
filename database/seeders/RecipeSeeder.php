<?php

namespace Database\Seeders;

use App\DTOs\ParsedIngredient;
use App\DTOs\RecipePlanItemInput;
use App\Enums\MealSlot;
use App\Enums\RecipeSource;
use App\Enums\UserRole;
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
 * The recipes themselves live in database/data/bangladeshi-recipes-80.json, where
 * every amount is already a number and a unit: "500–650 ml" became 575 ml when
 * the file was written, not when the seeder runs. Nothing here parses a
 * sentence, because nothing here has to.
 *
 * Re-running is safe. Recipes key on their slug, ingredient rows on their
 * position within a recipe, and the demo plan is rebuilt from scratch, so a
 * second run neither duplicates a row nor leaves an edited one behind.
 *
 * @phpstan-type DatasetIngredient array{ingredient_slug: string, ingredient_name: string, quantity: float|int, unit: string, is_optional?: bool}
 * @phpstan-type DatasetRecipe array{name: string, name_bn?: string, slug: string, category: string, servings: int, prep_minutes?: int, cook_minutes?: int, spice_level?: string, ingredients: array<int, DatasetIngredient>, steps: array<int, string>, notes?: string}
 * @phpstan-type RecipeSeedIngredient array{ingredient: string, quantity: float|int|null, unit: ?string, is_optional: bool, note: ?string, raw_text: string}
 * @phpstan-type RecipeSeedRow array{slug: string, title: string, name_bn: ?string, cuisine: string, category: string, servings: int, prep_minutes: ?int, cook_minutes: ?int, spice_level: ?string, image_url: ?string, instructions: string, ingredients: array<int, RecipeSeedIngredient>}
 */
class RecipeSeeder extends Seeder
{
    private const DATA_FILE = 'data/bangladeshi-recipes-80.json';

    /**
     * The dataset groups dishes the way a Bangladeshi cook would; the
     * storefront offers its own vocabulary. Bhorta earned a category of its
     * own rather than being folded into Vegetarian — ten of these dishes are
     * bhorta or bhaji, and that is how anyone here would look for them.
     */
    private const CATEGORY_MAP = [
        'rice_one_pot' => 'Rice & Biryani',
        'chicken' => 'Chicken',
        'beef_mutton' => 'Beef & Mutton',
        'fish_seafood' => 'Seafood',
        'dal_vegetable' => 'Vegetarian',
        'bhorta_bhaji' => 'Bhorta & Bhaji',
        'snack_breakfast' => 'Snack & Street Food',
        'dessert' => 'Dessert',
    ];

    /**
     * The dataset files breakfast and snacks together. Paratha and luchi are
     * what a morning looks like; the fried three are what a street stall
     * looks like, so they are sorted by hand rather than in bulk.
     */
    private const CATEGORY_BY_SLUG = [
        'paratha' => 'Breakfast',
        'luchi' => 'Breakfast',
    ];

    /**
     * The photographs that came with the recipes this set replaces, kept
     * where the dish is still the same dish. The rest have no image: a
     * gradient is better than a picture of something else.
     */
    private const IMAGES = [
        'beef-bhuna' => 'https://images.unsplash.com/photo-1544025162-d76694265947?auto=format&fit=crop&w=800&q=80',
        'masoor-dal' => 'https://images.unsplash.com/photo-1546833999-b9f581a1996d?auto=format&fit=crop&w=800&q=80',
        'begun-bhorta' => 'https://images.unsplash.com/photo-1563245372-f21724e3856d?auto=format&fit=crop&w=800&q=80',
        'chicken-roast' => 'https://images.unsplash.com/photo-1599488615731-7e5c2823ff28?auto=format&fit=crop&w=800&q=80',
    ];

    private const DEMO_PLAN_NAME = 'This week';

    /**
     * Everything this dataset replaces, by the slug it used to have. The
     * dishes themselves are all still here — kala bhuna, the roast, the polao,
     * the shorshe ilish — under the slugs the dataset gives them, so these
     * rows have to be named to be cleared out.
     */
    private const SUPERSEDED_SLUGS = [
        'smoky-begun-bharta',
        'panch-phoron-masoor-dal-tadka',
        'chittagong-beef-kala-bhuna',
        'bangladeshi-chicken-roast',
        'bangladeshi-polao',
        'rui-machher-jhol',
        'dudh-semai',
        'traditional-shorshe-ilish',
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
        /**
         * The demo cook writes the whole catalogue, so they have to be a
         * creator: a member cannot post at all, and seeding eighty published
         * recipes under an account that could not have written one of them
         * would be a demo of a state the application does not allow.
         */
        $demoUser = User::firstOrCreate(
            ['email' => 'demo@panchforon.com'],
            [
                'name' => 'Ahmed Zobayer',
                'password' => Hash::make('password'),
                'role' => UserRole::Creator,
            ]
        );

        $demoUser->forceFill(['role' => UserRole::Creator])->save();

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

        foreach ($this->packRecipes() as $data) {
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
     * The dataset, as the seeder needs it.
     *
     * The file is kept exactly as it was delivered — metadata, validation
     * status and all — so refreshing the catalogue is a file swap rather than
     * a rewrite. Everything the app's own shape needs is worked out here.
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

        /** @var array{recipes: array<int, DatasetRecipe>} $data */
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        return array_map(fn (array $recipe): array => $this->toSeedRow($recipe), $data['recipes']);
    }

    /**
     * @param  DatasetRecipe  $recipe
     * @return RecipeSeedRow
     */
    private function toSeedRow(array $recipe): array
    {
        $slug = $recipe['slug'];
        $group = $recipe['category'];

        if (! isset(self::CATEGORY_MAP[$group])) {
            throw new RuntimeException("Recipe {$slug} is filed under \"{$group}\", which has no place in the storefront's categories. Add it to CATEGORY_MAP.");
        }

        return [
            'slug' => $slug,
            'title' => $recipe['name'],
            'name_bn' => $recipe['name_bn'] ?? null,
            'cuisine' => 'Bangladeshi',
            'category' => self::CATEGORY_BY_SLUG[$slug] ?? self::CATEGORY_MAP[$group],
            'servings' => $recipe['servings'],
            'prep_minutes' => $recipe['prep_minutes'] ?? null,
            'cook_minutes' => $recipe['cook_minutes'] ?? null,
            'spice_level' => $recipe['spice_level'] ?? null,
            'image_url' => self::IMAGES[$slug] ?? null,
            'instructions' => $this->instructionsFor($recipe),
            'ingredients' => array_map(fn (array $row): array => $this->toIngredientRow($row), $recipe['ingredients']),
        ];
    }

    /**
     * The steps, numbered the way the catalogue reads them, with the recipe's
     * aside kept at the end — there is no column for it, and dropping a line
     * about dum heat would lose the only warning the recipe gives.
     *
     * @param  DatasetRecipe  $recipe
     */
    private function instructionsFor(array $recipe): string
    {
        $steps = [];

        foreach ($recipe['steps'] as $index => $step) {
            $steps[] = ($index + 1).'. '.$step;
        }

        $instructions = implode("\n", $steps);

        if (isset($recipe['notes']) && trim($recipe['notes']) !== '') {
            $instructions .= "\n\nNote: ".trim($recipe['notes']);
        }

        return $instructions;
    }

    /**
     * One ingredient row, with the line a cook would have written rebuilt from
     * the numbers. raw_text is not how the shopping list reads this row — the
     * quantity and unit columns are — but the column exists, and a sentence is
     * more use in it than a repeated name.
     *
     * @param  DatasetIngredient  $row
     * @return RecipeSeedIngredient
     */
    private function toIngredientRow(array $row): array
    {
        $name = mb_strtolower($row['ingredient_name']);
        $unit = $row['unit'];
        $amount = rtrim(rtrim(number_format((float) $row['quantity'], 2, '.', ''), '0'), '.');

        return [
            'ingredient' => $name,
            'quantity' => $row['quantity'],
            'unit' => $unit,
            'is_optional' => $row['is_optional'] ?? false,
            'note' => null,
            /** "4 green chili" rather than "4 piece green chili": nobody writes the second one. */
            'raw_text' => $unit === 'piece' ? "{$amount} {$name}" : "{$amount} {$unit} {$name}",
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
            'kala-bhuna' => [
                ['user' => $demoUser, 'stars' => 5, 'review' => 'Chittagong in a pot. Do not rush the last twenty minutes.'],
            ],
            'rui-macher-jhol' => [
                ['user' => $reviewerUser, 'stars' => 4, 'review' => 'Light, clean gravy. The kalojira is only optional on paper.'],
            ],
            'shorshe-ilish' => [
                ['user' => $reviewerUser, 'stars' => 5, 'review' => 'Pungent, authentic, and truly delicious with steaming hot rice.'],
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
            'sada-polao' => [
                ['user' => $reviewerUser, 'stars' => 5, 'review' => 'Fluffy and fragrant, and it does not fight the roast for attention.'],
            ],
            'chicken-roast' => [
                ['user' => $reviewerUser, 'stars' => 5, 'review' => 'Absolute wedding-style Biye Bari roast flavor! Perfectly balanced sweetness.'],
                ['user' => $demoUser, 'stars' => 5, 'review' => 'A family staple recipe that never fails.'],
            ],
            'kacchi-biryani' => [
                ['user' => $reviewerUser, 'stars' => 5, 'review' => 'Two hours of dum and the mutton falls off the bone. Worth clearing an afternoon for.'],
            ],
            'shemai' => [
                ['user' => $demoUser, 'stars' => 5, 'review' => 'Eid morning in a bowl. Take it off the heat looser than you think.'],
            ],
            'firni' => [
                ['user' => $demoUser, 'stars' => 4, 'review' => 'Set it in clay bowls if you have them; it chills better and tastes older.'],
            ],
        ];
    }
}
