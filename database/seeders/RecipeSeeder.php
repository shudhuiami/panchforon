<?php

namespace Database\Seeders;

use App\DTOs\RecipePlanItemInput;
use App\Enums\RecipeSource;
use App\Models\Ingredient;
use App\Models\MealPlan;
use App\Models\MealPlanItem;
use App\Models\Rating;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\ShoppingListItem;
use App\Models\User;
use App\Services\IngredientParser;
use App\Services\MergeEngine;
use App\Services\RankingService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RecipeSeeder extends Seeder
{
    public function run(
        IngredientParser $parser,
        RankingService $rankingService,
        MergeEngine $mergeEngine,
    ): void {
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

        $recipesData = [
            [
                'title' => 'Bangladeshi Chicken Roast',
                'cuisine' => 'Bangladeshi',
                'category' => 'Chicken',
                'servings' => 4,
                'instructions' => "1. Marinate chicken pieces in yogurt, ginger paste, garlic paste, and salt for 30 minutes.\n2. Shallow fry the chicken in oil until lightly golden, do not over-brown.\n3. In ghee and oil, fry beresta (crispy sliced onions), remove half for garnish.\n4. Add whole spices, onion paste, ginger-garlic paste, and spice powder.\n5. Add the chicken back, stir well, add milk and sugar, simmer on low until tender and oil floats on top.",
                'image_url' => 'https://images.unsplash.com/photo-1599488615731-7e5c2823ff28?auto=format&fit=crop&w=800&q=80',
                'ingredients' => [
                    ['raw' => '800g chicken', 'canonical' => 'chicken breast', 'qty' => 800, 'unit' => 'g'],
                    ['raw' => '2 onions, sliced', 'canonical' => 'onion', 'qty' => 2, 'unit' => 'piece'],
                    ['raw' => '2 tbsp ginger paste', 'canonical' => 'ginger', 'qty' => 2, 'unit' => 'tbsp'],
                    ['raw' => '2 tbsp garlic paste', 'canonical' => 'garlic', 'qty' => 2, 'unit' => 'tbsp'],
                    ['raw' => '1 cup milk', 'canonical' => 'milk', 'qty' => 1, 'unit' => 'cup'],
                    ['raw' => '1 tsp sugar', 'canonical' => 'sugar', 'qty' => 1, 'unit' => 'tsp'],
                    ['raw' => 'salt to taste', 'canonical' => 'salt', 'qty' => null, 'unit' => null],
                ],
                'ratings' => [
                    ['user' => $reviewerUser, 'stars' => 5, 'review' => 'Absolute wedding-style Biye Bari roast flavor! Perfectly balanced sweetness.'],
                    ['user' => $demoUser, 'stars' => 5, 'review' => 'A family staple recipe that never fails.'],
                ],
            ],
            [
                'title' => 'Traditional Shorshe Ilish',
                'cuisine' => 'Bangladeshi',
                'category' => 'Seafood',
                'servings' => 4,
                'instructions' => "1. Wash and pat dry Ilish steaks. Rub with turmeric and salt.\n2. Grind yellow and black mustard seeds with green chilies and a pinch of salt into a fine paste.\n3. Heat mustard oil until smoking, lower heat and temper with kalonji.\n4. Add mustard paste, turmeric, and slit green chilies with 1/2 cup warm water.\n5. Gently place the fish steaks into the bubbling gravy. Cook covered for 10 minutes.",
                'image_url' => 'https://images.unsplash.com/photo-1534422298391-e4f8c172dddb?auto=format&fit=crop&w=800&q=80',
                'ingredients' => [
                    ['raw' => '500g ilish fish steaks', 'canonical' => 'ilish', 'qty' => 500, 'unit' => 'g'],
                    ['raw' => '3 tbsp mustard oil', 'canonical' => 'mustard oil', 'qty' => 3, 'unit' => 'tbsp'],
                    ['raw' => '1 tsp turmeric powder', 'canonical' => 'turmeric', 'qty' => 1, 'unit' => 'tsp'],
                    ['raw' => '4 green chilies, slit', 'canonical' => 'chili', 'qty' => 4, 'unit' => 'piece'],
                    ['raw' => 'salt to taste', 'canonical' => 'salt', 'qty' => null, 'unit' => null],
                ],
                'ratings' => [
                    ['user' => $reviewerUser, 'stars' => 5, 'review' => 'Pungent, authentic, and truly delicious with steaming hot rice.'],
                ],
            ],
            [
                'title' => 'Chittagong Beef Kala Bhuna',
                'cuisine' => 'Bangladeshi',
                'category' => 'Beef',
                'servings' => 6,
                'instructions' => "1. Mix beef with fried onion, mustard oil, garlic, ginger, and kala bhuna spice mix.\n2. Cook on medium heat in a heavy bottom pot until meat releases its water and turns dark.\n3. Slowly slow-roast over low heat for 2 hours, continually scraping the bottom until blackened without burning.\n4. Temper with fried garlic, shallots, and dry red chilies in hot mustard oil.",
                'image_url' => 'https://images.unsplash.com/photo-1544025162-d76694265947?auto=format&fit=crop&w=800&q=80',
                'ingredients' => [
                    ['raw' => '1 kg beef chuck, cubed', 'canonical' => 'beef', 'qty' => 1, 'unit' => 'kg'],
                    ['raw' => '3 onions, sliced', 'canonical' => 'onion', 'qty' => 3, 'unit' => 'piece'],
                    ['raw' => '4 tbsp mustard oil', 'canonical' => 'mustard oil', 'qty' => 4, 'unit' => 'tbsp'],
                    ['raw' => '2 tbsp ginger paste', 'canonical' => 'ginger', 'qty' => 2, 'unit' => 'tbsp'],
                    ['raw' => '2 tbsp garlic paste', 'canonical' => 'garlic', 'qty' => 2, 'unit' => 'tbsp'],
                    ['raw' => '1 tbsp garam masala', 'canonical' => 'garam masala', 'qty' => 1, 'unit' => 'tbsp'],
                    ['raw' => 'salt to taste', 'canonical' => 'salt', 'qty' => null, 'unit' => null],
                ],
                'ratings' => [
                    ['user' => $reviewerUser, 'stars' => 5, 'review' => 'The smoky, deep dark crust is unbelievable! Best Kala Bhuna recipe.'],
                ],
            ],
            [
                'title' => 'Smoky Begun Bharta',
                'cuisine' => 'Bangladeshi',
                'category' => 'Vegetarian',
                'servings' => 4,
                'instructions' => "1. Slit eggplant and insert garlic cloves into the cuts. Rub with mustard oil.\n2. Roast directly over an open gas flame until charred and completely tender.\n3. Peel charred skin, mash flesh with chopped raw red onion, toasted dry red chili, salt, and raw mustard oil.",
                'image_url' => 'https://images.unsplash.com/photo-1563245372-f21724e3856d?auto=format&fit=crop&w=800&q=80',
                'ingredients' => [
                    ['raw' => '2 eggplants, whole', 'canonical' => 'eggplant', 'qty' => 2, 'unit' => 'piece'],
                    ['raw' => '1 onion, finely chopped', 'canonical' => 'onion', 'qty' => 1, 'unit' => 'piece'],
                    ['raw' => '4 garlic cloves', 'canonical' => 'garlic', 'qty' => 4, 'unit' => 'clove'],
                    ['raw' => '2 tbsp mustard oil', 'canonical' => 'mustard oil', 'qty' => 2, 'unit' => 'tbsp'],
                    ['raw' => '3 green chilies', 'canonical' => 'chili', 'qty' => 3, 'unit' => 'piece'],
                    ['raw' => 'salt to taste', 'canonical' => 'salt', 'qty' => null, 'unit' => null],
                ],
                'ratings' => [
                    ['user' => $demoUser, 'stars' => 4, 'review' => 'Rustic and fiery comforting mash.'],
                ],
            ],
            [
                'title' => 'Panch Phoron Masoor Dal Tadka',
                'cuisine' => 'Bangladeshi',
                'category' => 'Vegetarian',
                'servings' => 4,
                'instructions' => "1. Boil red lentils with water, turmeric powder, and salt until completely soft.\n2. In a separate pan, heat mustard oil or ghee. Crackle panch phoron seeds and dried red chilies.\n3. Add sliced onions and garlic; saute until golden brown.\n4. Pour aromatic tadka sizzle into the dal, top with fresh coriander leaves.",
                'image_url' => 'https://images.unsplash.com/photo-1546833999-b9f581a1996d?auto=format&fit=crop&w=800&q=80',
                'ingredients' => [
                    ['raw' => '200g red lentils', 'canonical' => 'red lentil', 'qty' => 200, 'unit' => 'g'],
                    ['raw' => '1 tbsp panch phoron', 'canonical' => 'panch phoron', 'qty' => 1, 'unit' => 'tbsp'],
                    ['raw' => '1 onion, sliced', 'canonical' => 'onion', 'qty' => 1, 'unit' => 'piece'],
                    ['raw' => '3 garlic cloves, sliced', 'canonical' => 'garlic', 'qty' => 3, 'unit' => 'clove'],
                    ['raw' => '1 tsp turmeric', 'canonical' => 'turmeric', 'qty' => 1, 'unit' => 'tsp'],
                    ['raw' => '2 tbsp coriander, chopped', 'canonical' => 'coriander', 'qty' => 2, 'unit' => 'tbsp'],
                    ['raw' => 'salt to taste', 'canonical' => 'salt', 'qty' => null, 'unit' => null],
                ],
                'ratings' => [
                    ['user' => $reviewerUser, 'stars' => 5, 'review' => 'Pure comfort food for any Bengali dinner table.'],
                ],
            ],
        ];

        $createdRecipes = [];

        foreach ($recipesData as $r) {
            $recipe = Recipe::create([
                'user_id' => $demoUser->id,
                'source' => RecipeSource::User,
                'title' => $r['title'],
                'slug' => Str::slug($r['title']),
                'cuisine' => $r['cuisine'],
                'category' => $r['category'],
                'instructions' => $r['instructions'],
                'image_url' => $r['image_url'],
                'servings' => $r['servings'],
                'source_url' => null,
            ]);

            $pos = 0;
            foreach ($r['ingredients'] as $ingData) {
                $ing = Ingredient::where('canonical_name', $ingData['canonical'])->first();

                RecipeIngredient::create([
                    'recipe_id' => $recipe->id,
                    'ingredient_id' => $ing?->id,
                    'quantity' => $ingData['qty'],
                    'unit' => $ingData['unit'],
                    'raw_text' => $ingData['raw'],
                    'position' => $pos++,
                ]);
            }

            foreach ($r['ratings'] as $rt) {
                Rating::create([
                    'user_id' => $rt['user']->id,
                    'recipe_id' => $recipe->id,
                    'stars' => $rt['stars'],
                    'review' => $rt['review'],
                ]);
            }

            $rankingService->updateRecipeStats($recipe->id);
            $createdRecipes[] = $recipe;
        }

        // Setup demo active meal plan with items and shopping list
        $plan = MealPlan::create([
            'user_id' => $demoUser->id,
            'name' => 'This week',
            'is_active' => true,
        ]);

        // Add 3 recipes to this plan
        $planRecipes = array_slice($createdRecipes, 0, 3);
        $planInputs = [];

        foreach ($planRecipes as $pr) {
            MealPlanItem::create([
                'meal_plan_id' => $plan->id,
                'recipe_id' => $pr->id,
                'servings' => 4,
            ]);

            $multiplier = 4 / ($pr->servings ?: 4);

            foreach ($pr->ingredients as $ri) {
                $parsed = $parser->parse($ri->raw_text);
                if ($ri->ingredient_id) {
                    $parsed = $parsed->withIngredientId($ri->ingredient_id);
                }
                if ($ri->ingredient && $ri->ingredient->default_dimension) {
                    $parsed = $parsed->withDimension($ri->ingredient->default_dimension);
                }

                $planInputs[] = new RecipePlanItemInput(
                    ingredient: $parsed,
                    servingsMultiplier: $multiplier,
                    recipeTitle: $pr->title,
                );
            }
        }

        // Generate shopping list lines
        $mergedLines = $mergeEngine->merge($planInputs);
        foreach ($mergedLines as $line) {
            ShoppingListItem::create([
                'meal_plan_id' => $plan->id,
                'ingredient_id' => $line->ingredientId,
                'display_name' => $line->displayName,
                'quantity' => $line->quantity,
                'unit' => $line->unit,
                'is_unmerged' => $line->isUnmerged,
                'source_note' => $line->sourceNote,
                'is_checked' => false,
            ]);
        }
    }
}
