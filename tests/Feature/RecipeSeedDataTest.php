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

test('the trial pack and the recipe it did not replace are all present', function () {
    $this->seed(DatabaseSeeder::class);

    $slugs = Recipe::query()->pluck('slug');

    expect($slugs)->toHaveCount(11)
        ->toContain('bangladeshi-chicken-curry', 'beef-bhuna', 'rui-machher-jhol', 'masoor-dal')
        ->toContain('aloo-bhorta', 'begun-bhorta', 'bhuna-khichuri', 'bangladeshi-polao')
        ->toContain('bangladeshi-chicken-roast', 'dudh-semai')
        ->toContain('traditional-shorshe-ilish')
        ->not->toContain('smoky-begun-bharta', 'panch-phoron-masoor-dal-tadka', 'chittagong-beef-kala-bhuna');
});

test('water is a real ingredient that nobody has to buy', function () {
    $this->seed(DatabaseSeeder::class);

    $water = Ingredient::query()->where('canonical_name', 'water')->sole();

    expect($water->is_shoppable)->toBeFalse()
        ->and($water->default_dimension)->toBe(UnitDimension::Volume);

    $row = seededRow('bangladeshi-chicken-curry', 'water');

    expect($row->quantity)->toBe(575.0)
        ->and($row->unit)->toBe('ml');

    expect(ShoppingListItem::query()->where('ingredient_id', $water->id)->exists())->toBeFalse();
});

test('optional is a property of the row, not of the ingredient', function () {
    $this->seed(DatabaseSeeder::class);

    expect(seededRow('bangladeshi-chicken-curry', 'yogurt')->is_optional)->toBeTrue()
        ->and(seededRow('beef-bhuna', 'yogurt')->is_optional)->toBeFalse();

    expect(seededRow('bangladeshi-chicken-curry', 'tomato')->is_optional)->toBeTrue()
        ->and(seededRow('rui-machher-jhol', 'tomato')->is_optional)->toBeFalse();

    expect(seededRow('bhuna-khichuri', 'cauliflower')->is_optional)->toBeTrue()
        ->and(seededRow('bangladeshi-chicken-roast', 'kewra water')->is_optional)->toBeTrue()
        ->and(seededRow('dudh-semai', 'milk powder')->is_optional)->toBeTrue()
        ->and(seededRow('bangladeshi-chicken-curry', 'chicken')->is_optional)->toBeFalse();

    // 16 across the trial pack, plus the kalonji in the older shorshe ilish.
    expect(RecipeIngredient::query()->where('is_optional', true)->count())->toBe(17);
});

test('quantities were normalised into numbers and known units, not left as text', function () {
    $this->seed(DatabaseSeeder::class);

    $unitSymbols = Unit::query()->pluck('symbol')->all();

    foreach (RecipeIngredient::query()->whereNotNull('quantity')->get() as $row) {
        expect($row->quantity)->toBeFloat()
            ->and($row->quantity)->toBeGreaterThan(0)
            ->and($row->unit)->toBeIn($unitSymbols);
    }

    // The only amount-less rows in the catalogue are the two pinches of salt.
    $withoutQuantity = RecipeIngredient::query()->whereNull('quantity')->get();

    expect($withoutQuantity)->toHaveCount(2)
        ->and($withoutQuantity->pluck('unit')->filter())->toBeEmpty();

    // Ranges were resolved when the data file was written: 500-650 ml became
    // 575, 1.2-1.4 L became 1300, and a count rounded up rather than halving.
    expect(seededRow('bhuna-khichuri', 'water')->quantity)->toBe(1300.0)
        ->and(seededRow('bangladeshi-polao', 'water')->quantity)->toBe(560.0)
        ->and(seededRow('bangladeshi-chicken-roast', 'chicken')->quantity)->toBe(4.0)
        ->and(seededRow('bangladeshi-chicken-roast', 'chicken')->unit)->toBe('piece');

    // The household measure survives as a note, where a cook can use it.
    expect(seededRow('bangladeshi-chicken-curry', 'potato')->note)->toBe('3 medium, halved');
});

test('recipes carry their Bangla name, their timings and a spice level', function () {
    $this->seed(DatabaseSeeder::class);

    $curry = seededRecipe('bangladeshi-chicken-curry');

    expect($curry->name_bn)->toBe('মুরগির ঝোল')
        ->and($curry->prep_minutes)->toBe(20)
        ->and($curry->cook_minutes)->toBe(45)
        ->and($curry->spice_level)->toBe(SpiceLevel::Medium)
        ->and($curry->servings)->toBe(4)
        ->and($curry->cuisine)->toBe('Bangladeshi')
        ->and($curry->category)->toBe('Chicken');

    expect(seededRecipe('beef-bhuna')->spice_level)->toBe(SpiceLevel::Hot)
        ->and(seededRecipe('masoor-dal')->spice_level)->toBe(SpiceLevel::Mild)
        ->and(seededRecipe('aloo-bhorta')->spice_level)->toBeNull();

    expect(Recipe::query()->whereNull('name_bn')->count())->toBe(0);
});

test('the ingredient dictionary measures the produce the way the recipes do', function () {
    $this->seed(DatabaseSeeder::class);

    $dimensions = Ingredient::query()
        ->whereIn('canonical_name', ['potato', 'onion', 'tomato', 'garlic', 'eggplant', 'coriander'])
        ->pluck('default_dimension', 'canonical_name');

    foreach ($dimensions as $canonicalName => $dimension) {
        expect($dimension)->toBe(UnitDimension::Mass, "{$canonicalName} is weighed in this dataset");
    }

    $ginger = Ingredient::query()->where('canonical_name', 'ginger')->sole();

    expect($ginger->name_bn)->toBe('আদা')
        ->and($ginger->preferred_unit)->toBe('g');

    // "ginger paste" is the same thing in the trolley; polao rice is not.
    expect(IngredientAlias::query()->where('alias', 'ginger paste')->sole()->ingredient_id)->toBe($ginger->id)
        ->and(IngredientAlias::query()->where('alias', 'polao rice')->sole()->ingredient_id)
        ->toBe(Ingredient::query()->where('canonical_name', 'aromatic rice')->sole()->id);
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
        ->and($onionLines->first()->quantity)->toBe(480.0)
        ->and($onionLines->first()->unit)->toBe('g')
        ->and($onionLines->first()->display_name)->toBe('onion');

    expect(Rating::query()->where('user_id', $demoUser->id)->count())->toBeGreaterThan(0);
});
