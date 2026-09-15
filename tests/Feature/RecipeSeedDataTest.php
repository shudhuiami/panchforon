<?php

use App\Enums\SpiceLevel;
use App\Enums\UnitDimension;
use App\Models\Ingredient;
use App\Models\IngredientAlias;
use App\Models\MealPlan;
use App\Models\MealPlanItem;
use App\Models\Rating;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\ShoppingListItem;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\IngredientSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Everything the catalogue seeding writes, in one snapshot, so a second run
 * can be compared against the first row by row.
 *
 * @return array<string, int>
 */
function seededCatalogueCounts(): array
{
    return [
        'users' => User::query()->count(),
        'recipes' => Recipe::query()->count(),
        'recipe_ingredients' => RecipeIngredient::query()->count(),
        'ingredients' => Ingredient::query()->count(),
        'ingredient_aliases' => IngredientAlias::query()->count(),
        'ratings' => Rating::query()->count(),
        'meal_plans' => MealPlan::query()->count(),
        'meal_plan_items' => MealPlanItem::query()->count(),
        'shopping_list_items' => ShoppingListItem::query()->count(),
    ];
}

function seededRecipe(string $slug): Recipe
{
    return Recipe::query()->where('slug', $slug)->sole();
}

function seededRow(string $slug, string $canonicalName): RecipeIngredient
{
    return RecipeIngredient::query()
        ->where('recipe_id', seededRecipe($slug)->id)
        ->whereHas('ingredient', fn ($query) => $query->where('canonical_name', $canonicalName))
        ->sole();
}

test('seeding twice leaves exactly one of everything', function () {
    $this->seed(DatabaseSeeder::class);
    $afterFirstRun = seededCatalogueCounts();

    $this->seed(DatabaseSeeder::class);

    expect(seededCatalogueCounts())->toBe($afterFirstRun);

    $duplicateSlugs = Recipe::query()
        ->selectRaw('slug, COUNT(*) as total')
        ->groupBy('slug')
        ->havingRaw('COUNT(*) > 1')
        ->pluck('slug');

    $duplicateIngredients = Ingredient::query()
        ->selectRaw('canonical_name, COUNT(*) as total')
        ->groupBy('canonical_name')
        ->havingRaw('COUNT(*) > 1')
        ->pluck('canonical_name');

    expect($duplicateSlugs)->toBeEmpty()
        ->and($duplicateIngredients)->toBeEmpty();
});

test('the whole catalogue is seeded, and what it replaced is gone', function () {
    $this->seed(DatabaseSeeder::class);

    $slugs = Recipe::query()->pluck('slug');

    expect($slugs)->toHaveCount(80)
        ->toContain('bangladeshi-chicken-curry', 'beef-bhuna', 'kala-bhuna', 'masoor-dal')
        ->toContain('aloo-bhorta', 'begun-bhorta', 'bhuna-khichuri', 'sada-polao')
        ->toContain('chicken-roast', 'kacchi-biryani', 'shorshe-ilish', 'shemai')
        /** The dishes survive; the slugs they used to sit under do not. */
        ->not->toContain('smoky-begun-bharta', 'panch-phoron-masoor-dal-tadka', 'chittagong-beef-kala-bhuna')
        ->not->toContain('bangladeshi-chicken-roast', 'bangladeshi-polao', 'rui-machher-jhol')
        ->not->toContain('dudh-semai', 'traditional-shorshe-ilish');
});

test('every recipe is filed under a category the storefront offers', function () {
    $this->seed(DatabaseSeeder::class);

    /** The list COMMON_CATEGORIES offers in the recipe form. */
    $offered = ['Curry', 'Rice & Biryani', 'Seafood', 'Chicken', 'Beef & Mutton', 'Vegetarian', 'Bhorta & Bhaji', 'Dessert', 'Breakfast', 'Snack & Street Food'];

    foreach (Recipe::query()->get(['slug', 'category', 'cuisine']) as $recipe) {
        expect($recipe->category)->toBeIn($offered)
            ->and($recipe->cuisine)->toBe('Bangladeshi');
    }

    /** Breakfast and street food arrive as one group and are sorted by hand. */
    expect(seededRecipe('paratha')->category)->toBe('Breakfast')
        ->and(seededRecipe('luchi')->category)->toBe('Breakfast')
        ->and(seededRecipe('singara')->category)->toBe('Snack & Street Food')
        ->and(seededRecipe('aloo-bhorta')->category)->toBe('Bhorta & Bhaji');
});

test('water is a real ingredient that nobody has to buy', function () {
    $this->seed(DatabaseSeeder::class);

    $water = Ingredient::query()->where('canonical_name', 'water')->sole();
    $hotWater = Ingredient::query()->where('canonical_name', 'hot water')->sole();

    expect($water->is_shoppable)->toBeFalse()
        ->and($water->default_dimension)->toBe(UnitDimension::Volume)
        ->and($hotWater->is_shoppable)->toBeFalse();

    $row = seededRow('bangladeshi-chicken-curry', 'water');

    expect($row->quantity)->toBe(650.0)
        ->and($row->unit)->toBe('ml');

    /** Dozens of recipes call for it; no shopping list ever does. */
    expect(RecipeIngredient::query()->whereIn('ingredient_id', [$water->id, $hotWater->id])->count())->toBeGreaterThan(40)
        ->and(ShoppingListItem::query()->whereIn('ingredient_id', [$water->id, $hotWater->id])->exists())->toBeFalse();
});

test('optional is a property of the row, not of the ingredient', function () {
    $this->seed(DatabaseSeeder::class);

    /** The same thing, optional in one dish and required in the next. */
    expect(seededRow('steamed-rice', 'salt')->is_optional)->toBeTrue()
        ->and(seededRow('sada-polao', 'salt')->is_optional)->toBeFalse();

    expect(seededRow('paratha', 'ghee')->is_optional)->toBeTrue()
        ->and(seededRow('sada-polao', 'ghee')->is_optional)->toBeFalse();

    expect(seededRow('aloo-bhorta', 'fresh coriander')->is_optional)->toBeTrue()
        ->and(seededRow('piyaju', 'fresh coriander')->is_optional)->toBeFalse();

    expect(seededRow('chicken-roast', 'kewra water')->is_optional)->toBeTrue()
        ->and(seededRow('bangladeshi-chicken-curry', 'chicken')->is_optional)->toBeFalse();

    expect(RecipeIngredient::query()->where('is_optional', true)->count())->toBe(33);
});

test('quantities were normalised into numbers and known units, not left as text', function () {
    $this->seed(DatabaseSeeder::class);

    $unitSymbols = Unit::query()->pluck('symbol')->all();

    foreach (RecipeIngredient::query()->get() as $row) {
        expect($row->quantity)->toBeFloat()
            ->and($row->quantity)->toBeGreaterThan(0)
            ->and($row->unit)->toBeIn($unitSymbols);
    }

    /**
     * Nothing in this catalogue needs the parser: every row arrived as a
     * number and a unit, and the dataset resolved its own ranges before it
     * was written.
     */
    expect(RecipeIngredient::query()->whereNull('quantity')->count())->toBe(0)
        ->and(RecipeIngredient::query()->whereNull('ingredient_id')->count())->toBe(0);

    expect(seededRow('bhuna-khichuri', 'hot water')->quantity)->toBe(1200.0)
        ->and(seededRow('bangladeshi-chicken-curry', 'chicken')->quantity)->toBe(900.0)
        ->and(seededRow('bangladeshi-chicken-curry', 'chicken')->unit)->toBe('g');

    /** The sentence is rebuilt from the numbers, and a count leaves out "piece". */
    expect(seededRow('bangladeshi-chicken-curry', 'green chili')->raw_text)->toBe('4 green chili')
        ->and(seededRow('bangladeshi-chicken-curry', 'potato')->raw_text)->toBe('400 g potato');
});

test('recipes carry their Bangla name, their timings and a spice level', function () {
    $this->seed(DatabaseSeeder::class);

    $curry = seededRecipe('bangladeshi-chicken-curry');

    expect($curry->name_bn)->toBe('দেশি স্টাইল মুরগির ঝোল')
        ->and($curry->prep_minutes)->toBe(20)
        ->and($curry->cook_minutes)->toBe(45)
        ->and($curry->spice_level)->toBe(SpiceLevel::Medium)
        ->and($curry->servings)->toBe(4)
        ->and($curry->cuisine)->toBe('Bangladeshi')
        ->and($curry->category)->toBe('Chicken');

    expect(seededRecipe('beef-bhuna')->spice_level)->toBe(SpiceLevel::Hot)
        ->and(seededRecipe('masoor-dal')->spice_level)->toBe(SpiceLevel::Mild)
        ->and(seededRecipe('kacchi-biryani')->cook_minutes)->toBe(120);

    /** Every one of them is named in both languages and timed. */
    expect(Recipe::query()->whereNull('name_bn')->count())->toBe(0)
        ->and(Recipe::query()->whereNull('prep_minutes')->orWhereNull('cook_minutes')->count())->toBe(0)
        ->and(Recipe::query()->whereNull('spice_level')->count())->toBe(0);

    /** The steps arrive as a list and are written out numbered. */
    expect(seededRecipe('steamed-rice')->instructions)->toStartWith('1. Rinse rice');

    /** A recipe's aside has no column, so it is kept at the end of the method. */
    expect(seededRecipe('kacchi-biryani')->instructions)->toContain('Note: Use a heavy-bottomed pot');
});

test('the ingredient dictionary measures the produce the way the recipes do', function () {
    $this->seed(DatabaseSeeder::class);

    $dimensions = Ingredient::query()
        ->whereIn('canonical_name', ['potato', 'onion', 'tomato', 'garlic', 'eggplant', 'fresh coriander'])
        ->pluck('default_dimension', 'canonical_name');

    expect($dimensions)->toHaveCount(6);

    foreach ($dimensions as $canonicalName => $dimension) {
        expect($dimension)->toBe(UnitDimension::Mass, "{$canonicalName} is weighed in this dataset");
    }

    $ginger = Ingredient::query()->where('canonical_name', 'ginger')->sole();

    expect($ginger->name_bn)->toBe('আদা')
        ->and($ginger->preferred_unit)->toBe('g');

    /**
     * The dictionary is built from the dataset, so it holds exactly what the
     * recipes use — nothing defined that no dish calls for.
     */
    expect(Ingredient::query()->count())->toBe(90);

    /**
     * Aliases are the words people type. Ginger paste is its own purchase in
     * this dataset, so it is a row rather than an alias of ginger; the Bangla
     * names and the importer's English variants still resolve.
     */
    expect(IngredientAlias::query()->where('alias', 'shorsher tel')->sole()->ingredient_id)
        ->toBe(Ingredient::query()->where('canonical_name', 'mustard oil')->sole()->id)
        ->and(IngredientAlias::query()->where('alias', 'ilish mach')->sole()->ingredient_id)
        ->toBe(Ingredient::query()->where('canonical_name', 'hilsa')->sole()->id)
        ->and(Ingredient::query()->where('canonical_name', 'ginger paste')->exists())->toBeTrue()
        ->and(IngredientAlias::query()->where('alias', 'ginger paste')->exists())->toBeFalse();

    /** An alias may never shadow a canonical name — "rice" is its own row. */
    $canonicalNames = Ingredient::query()->pluck('canonical_name')->all();
    expect(IngredientAlias::query()->pluck('alias')->intersect($canonicalNames))->toBeEmpty();
});

test('re-seeding corrects a dimension that has drifted rather than leaving it', function () {
    $this->seed(DatabaseSeeder::class);

    Ingredient::query()
        ->where('canonical_name', 'potato')
        ->update(['default_dimension' => UnitDimension::Count, 'is_shoppable' => true]);

    Ingredient::query()->where('canonical_name', 'water')->update(['is_shoppable' => true]);

    $this->seed(IngredientSeeder::class);

    expect(Ingredient::query()->where('canonical_name', 'potato')->sole()->default_dimension)
        ->toBe(UnitDimension::Mass)
        ->and(Ingredient::query()->where('canonical_name', 'water')->sole()->is_shoppable)
        ->toBeFalse();
});

test('the demo cook still has an account, a week of meals and a shopping list', function () {
    $this->seed(DatabaseSeeder::class);

    $demoUser = User::query()->where('email', 'demo@panchforon.com')->sole();
    $plan = MealPlan::query()->where('user_id', $demoUser->id)->sole();

    expect($plan->is_active)->toBeTrue()
        ->and($plan->starts_on->toDateString())->toBe(today()->toDateString())
        ->and($plan->ends_on->toDateString())->toBe(today()->addDays(MealPlan::DEFAULT_DAYS - 1)->toDateString());

    expect($plan->items()->count())->toBe(3)
        ->and($plan->items()->whereNull('planned_for')->count())->toBe(0)
        ->and($plan->shoppingListItems()->count())->toBeGreaterThan(0);

    // Onion is in all three planned dishes, so the list carries one line for it.
    $onion = Ingredient::query()->where('canonical_name', 'onion')->sole();
    $onionLines = ShoppingListItem::query()->where('ingredient_id', $onion->id)->get();

    expect($onionLines)->toHaveCount(1)
        ->and($onionLines->first()->quantity)->toBe(520.0)
        ->and($onionLines->first()->unit)->toBe('g')
        ->and($onionLines->first()->display_name)->toBe('onion');

    expect(Rating::query()->where('user_id', $demoUser->id)->count())->toBeGreaterThan(0);
});
