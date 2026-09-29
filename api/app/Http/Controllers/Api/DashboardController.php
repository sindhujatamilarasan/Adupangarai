<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GroceryList;
use App\Models\MealPlan;
use App\Models\PantryItem;
use App\Models\Recipe;
use App\Support\RecipeMatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $household = $request->user()->household_id;
        $pantry = RecipeMatcher::pantryFor($household);
        $recipes = Recipe::visibleTo($household)->with('ingredients.ingredient')->get();
        $results = RecipeMatcher::matchAll($recipes, $pantry);

        $todayMeals = MealPlan::where('household_id', $household)->whereDate('date', Carbon::today())
            ->with('recipe:id,name,name_ta,is_veg,servings,meal_type,image_path')->orderBy('id')->get()
            ->sortBy(fn ($p) => array_search($p->meal_type, Recipe::MEAL_TYPES))->values()
            ->map(fn (MealPlan $p) => [
                'id' => $p->id, 'meal_type' => $p->meal_type, 'servings' => $p->servings, 'cooked_at' => $p->cooked_at,
                'recipe' => $p->recipe,
                'can_cook_now' => RecipeMatcher::match($recipes->firstWhere('id', $p->recipe_id), $pantry, $p->servings)['status'] === RecipeMatcher::AVAILABLE,
            ]);

        $list = GroceryList::where('household_id', $household)->first();

        return response()->json([
            'today_meals' => $todayMeals,
            'cook_now' => RecipeMatcher::rank($results->where('match.status', RecipeMatcher::AVAILABLE))->take(3)->values(),
            'almost_count' => $results->where('match.status', RecipeMatcher::ALMOST)->count(),
            'use_soon' => RecipeMatcher::useSoon($results)->take(3)->values(),
            'expiring' => PantryItem::where('household_id', $household)->view('expiring_soon')->where('quantity', '>', 0)
                ->with('ingredient:id,name,name_ta,icon')->orderBy('expiry_date')->limit(5)->get(),
            'expired_count' => PantryItem::where('household_id', $household)->view('expired')->where('quantity', '>', 0)->count(),
            'low_stock' => PantryItem::where('household_id', $household)->view('low_stock')
                ->with('ingredient:id,name,name_ta,icon')->orderBy('quantity')->limit(5)->get(),
            'grocery_remaining' => $list ? $list->items()->where('purchased', false)->count() : 0,
            'pantry_count' => $pantry->count(),
            'recipe_count' => $recipes->count(),
        ]);
    }
}
