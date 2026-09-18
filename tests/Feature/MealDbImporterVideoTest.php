<?php

use App\Services\MealDbImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * The fields TheMealDB always sends, so each test only has to say what it is
 * actually about.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function mealDbPayload(array $overrides = []): array
{
    return array_merge([
        'idMeal' => '52772',
        'strMeal' => 'Teriyaki Chicken Casserole',
        'strArea' => 'Japanese',
        'strCategory' => 'Chicken',
        'strInstructions' => 'Preheat oven to 350. Combine everything and bake.',
        'strMealThumb' => 'https://www.themealdb.com/images/media/meals/wvpsxx.jpg',
        'strSource' => 'https://example.test/teriyaki',
        'strIngredient1' => 'soy sauce',
        'strMeasure1' => '3/4 cup',
    ], $overrides);
}

test('an imported recipe keeps the id out of a watch url', function () {
    $recipe = app(MealDbImporter::class)->saveRecipe(mealDbPayload([
        'strYoutube' => 'https://www.youtube.com/watch?v=4aZr5hZXP_s',
    ]));

    expect($recipe->youtube_video_id)->toBe('4aZr5hZXP_s');
});

test('an import stores null rather than letting a bad value reach an embed', function (mixed $strYoutube) {
    $recipe = app(MealDbImporter::class)->saveRecipe(mealDbPayload(['strYoutube' => $strYoutube]));

    expect($recipe->youtube_video_id)->toBeNull();
})->with([
    'an empty field' => '',
    'a link somewhere else' => 'https://vimeo.com/76979871',
    'a watch url with no id on it' => 'https://www.youtube.com/watch?v=',
    'something that is not a url at all' => 'coming soon',
]);

test('an import with no video field at all leaves the column null', function () {
    $recipe = app(MealDbImporter::class)->saveRecipe(mealDbPayload());

    expect($recipe->youtube_video_id)->toBeNull();
});

test('re-importing a recipe takes the video with it', function () {
    $importer = app(MealDbImporter::class);

    $importer->saveRecipe(mealDbPayload());
    $reimported = $importer->saveRecipe(mealDbPayload(['strYoutube' => 'https://youtu.be/4aZr5hZXP_s']));

    expect($reimported->youtube_video_id)->toBe('4aZr5hZXP_s');
});
