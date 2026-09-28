<?php

use App\Http\Controllers\Api\AiController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CookController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\GroceryController;
use App\Http\Controllers\Api\IngredientController;
use App\Http\Controllers\Api\MealPlanController;
use App\Http\Controllers\Api\PantryController;
use App\Http\Controllers\Api\RecipeController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);

    Route::get('/dashboard', DashboardController::class);

    Route::get('/units', [IngredientController::class, 'units']);
    Route::get('/ingredient-categories', [IngredientController::class, 'categories']);
    Route::get('/ingredients', [IngredientController::class, 'index']);
    Route::post('/ingredients', [IngredientController::class, 'store']);

    Route::get('/pantry', [PantryController::class, 'index']);
    Route::post('/pantry', [PantryController::class, 'store']);
    Route::post('/pantry/bulk', [PantryController::class, 'bulk']);
    Route::post('/pantry/discard-expired', [PantryController::class, 'discardExpired']);
    Route::get('/pantry/{item}', [PantryController::class, 'show']);
    Route::patch('/pantry/{item}', [PantryController::class, 'update']);
    Route::post('/pantry/{item}/adjust', [PantryController::class, 'adjust']);
    Route::delete('/pantry/{item}', [PantryController::class, 'destroy']);

    Route::apiResource('recipes', RecipeController::class);
    Route::get('/recipes/{recipe}/cook', [RecipeController::class, 'cookPreview']);
    Route::post('/recipes/{recipe}/cook', [RecipeController::class, 'cook']);
    Route::post('/recipes/{recipe}/nutrition', [RecipeController::class, 'estimateNutrition'])->middleware('throttle:10,1');
    Route::get('/cook', [CookController::class, 'index']);

    Route::post('/meal-plans/bulk', [MealPlanController::class, 'bulk']);
    Route::apiResource('meal-plans', MealPlanController::class)->except('show');

    Route::middleware('throttle:20,1')->prefix('ai')->group(function () {
        Route::post('/pantry-parse', [AiController::class, 'pantryParse']);
        Route::post('/recipe-parse', [AiController::class, 'recipeParse']);
        Route::post('/meal-plan', [AiController::class, 'mealPlan']);
    });

    Route::get('/grocery', [GroceryController::class, 'index']);
    Route::post('/grocery/generate', [GroceryController::class, 'generate']);
    Route::post('/grocery/items', [GroceryController::class, 'storeItem']);
    Route::patch('/grocery/items/{item}', [GroceryController::class, 'updateItem']);
    Route::delete('/grocery/items/{item}', [GroceryController::class, 'destroyItem']);
    Route::post('/grocery/clear-purchased', [GroceryController::class, 'clearPurchased']);
    Route::post('/grocery/add-to-pantry', [GroceryController::class, 'addToPantry']);
});
