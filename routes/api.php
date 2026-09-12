<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ContentFlagController;
use App\Http\Controllers\Api\CookController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\MealPlanController;
use App\Http\Controllers\Api\PageController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\RatingController;
use App\Http\Controllers\Api\RecipeController;
use App\Http\Controllers\Api\RecipeSaveController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\SocialExchangeController;
use App\Http\Middleware\EnsureUserIsNotSuspended;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::middleware('throttle:auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [PasswordResetController::class, 'sendLink']);
    Route::post('/reset-password', [PasswordResetController::class, 'reset']);
    Route::post('/auth/social/exchange', SocialExchangeController::class);
});

Route::get('/recipes', [RecipeController::class, 'index']);
Route::get('/recipes/{slug}', [RecipeController::class, 'show'])->where('slug', '[a-z0-9\-]+');
Route::get('/cuisines', [RecipeController::class, 'cuisines']);
Route::get('/categories', [RecipeController::class, 'categories']);
Route::get('/settings', SettingsController::class);
Route::get('/home', HomeController::class);
Route::get('/cooks/{id}', [CookController::class, 'show'])->whereNumber('id');
Route::get('/pages', [PageController::class, 'index']);
Route::get('/content-blocks', [PageController::class, 'blocks']);
Route::get('/flag-reasons', [ContentFlagController::class, 'reasons']);
Route::get('/pages/{slug}', [PageController::class, 'show'])->where('slug', '[a-z0-9\-]+');

/*
|--------------------------------------------------------------------------
| Authenticated Routes (Sanctum)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', EnsureUserIsNotSuspended::class])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Account
    Route::put('/user', [AccountController::class, 'updateProfile']);
    Route::put('/user/password', [AccountController::class, 'updatePassword']);
    Route::get('/my/recipes', [AccountController::class, 'recipes']);
    Route::get('/my/ratings', [AccountController::class, 'ratings']);

    // Recipe CRUD (User-authored)
    Route::post('/recipes', [RecipeController::class, 'store']);
    Route::put('/recipes/{id}', [RecipeController::class, 'update'])->whereNumber('id');
    Route::delete('/recipes/{id}', [RecipeController::class, 'destroy'])->whereNumber('id');

    // Reporting a recipe
    Route::post('/recipes/{id}/flag', [ContentFlagController::class, 'store'])->whereNumber('id');

    // Ratings
    Route::put('/recipes/{id}/rating', [RatingController::class, 'upsert'])->whereNumber('id');
    Route::delete('/recipes/{id}/rating', [RatingController::class, 'destroy'])->whereNumber('id');

    // Saved recipes (the wishlist)
    Route::get('/saved-recipes', [RecipeSaveController::class, 'index']);
    Route::get('/saved-recipes/ids', [RecipeSaveController::class, 'ids']);
    Route::post('/recipes/{id}/save', [RecipeSaveController::class, 'store'])->whereNumber('id');
    Route::delete('/recipes/{id}/save', [RecipeSaveController::class, 'destroy'])->whereNumber('id');

    // Meal Plan
    Route::get('/meal-plan', [MealPlanController::class, 'show']);
    Route::post('/meal-plan/items', [MealPlanController::class, 'addItem']);
    Route::patch('/meal-plan/items/{id}', [MealPlanController::class, 'updateItem'])->whereNumber('id');
    Route::delete('/meal-plan/items/{id}', [MealPlanController::class, 'removeItem'])->whereNumber('id');

    // Shopping List
    Route::post('/meal-plan/shopping-list', [MealPlanController::class, 'generateShoppingList']);
    Route::get('/meal-plan/shopping-list', [MealPlanController::class, 'getShoppingList']);
    Route::patch('/shopping-list/items/{id}', [MealPlanController::class, 'toggleShoppingListItem'])->whereNumber('id');
});
