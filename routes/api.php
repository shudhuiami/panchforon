<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MealPlanController;
use App\Http\Controllers\Api\RatingController;
use App\Http\Controllers\Api\RecipeController;
use App\Http\Controllers\Api\SettingsController;
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
});

Route::get('/recipes', [RecipeController::class, 'index']);
Route::get('/recipes/{slug}', [RecipeController::class, 'show'])->where('slug', '[a-z0-9\-]+');
Route::get('/cuisines', [RecipeController::class, 'cuisines']);
Route::get('/categories', [RecipeController::class, 'categories']);
Route::get('/settings', SettingsController::class);

/*
|--------------------------------------------------------------------------
| Authenticated Routes (Sanctum)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', EnsureUserIsNotSuspended::class])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Recipe CRUD (User-authored)
    Route::post('/recipes', [RecipeController::class, 'store']);
    Route::put('/recipes/{id}', [RecipeController::class, 'update'])->whereNumber('id');
    Route::delete('/recipes/{id}', [RecipeController::class, 'destroy'])->whereNumber('id');

    // Ratings
    Route::put('/recipes/{id}/rating', [RatingController::class, 'upsert'])->whereNumber('id');
    Route::delete('/recipes/{id}/rating', [RatingController::class, 'destroy'])->whereNumber('id');

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
