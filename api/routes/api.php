<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\IngredientController;
use App\Http\Controllers\Api\PantryController;
use App\Http\Controllers\Api\RecipeController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);

    Route::get('/units', [IngredientController::class, 'units']);
    Route::get('/ingredient-categories', [IngredientController::class, 'categories']);
    Route::get('/ingredients', [IngredientController::class, 'index']);
    Route::post('/ingredients', [IngredientController::class, 'store']);

    Route::get('/pantry', [PantryController::class, 'index']);
    Route::post('/pantry', [PantryController::class, 'store']);
    Route::get('/pantry/{item}', [PantryController::class, 'show']);
    Route::patch('/pantry/{item}', [PantryController::class, 'update']);
    Route::post('/pantry/{item}/adjust', [PantryController::class, 'adjust']);
    Route::delete('/pantry/{item}', [PantryController::class, 'destroy']);

    Route::apiResource('recipes', RecipeController::class);
});
