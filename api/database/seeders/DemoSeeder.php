<?php

namespace Database\Seeders;

use App\Models\Household;
use App\Models\Ingredient;
use App\Models\MealPlan;
use App\Models\PantryItem;
use App\Models\Recipe;
use App\Models\User;
use App\Support\PantryLedger;
use App\Support\TransactionType;
use App\Support\Unit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/** Demo account with a stocked kitchen and this week's plan. Only fills what is empty. */
class DemoSeeder extends Seeder
{
    public const EMAIL = 'demo@adupangarai.test';

    /** [ingredient, quantity, unit, expires in days (null = no date), minimum stock, location] */
    private const PANTRY = [
        ['Rice', 5, 'kg', null, 1, 'pantry'], ['Basmati Rice', 1, 'kg', null, null, 'pantry'],
        ['Toor Dal', 1, 'kg', null, 0.25, 'pantry'], ['Moong Dal', 500, 'g', null, null, 'pantry'],
        ['Urad Dal', 250, 'g', null, null, 'pantry'], ['Wheat Flour', 2, 'kg', null, 0.5, 'pantry'],
        ['Rava', 500, 'g', null, null, 'pantry'], ['Egg', 6, 'piece', 10, 6, 'fridge'],
        ['Onion', 6, 'piece', null, 4, 'pantry'], ['Tomato', 4, 'piece', 4, 3, 'fridge'],
        ['Potato', 5, 'piece', null, null, 'pantry'], ['Green Chilli', 10, 'piece', 5, null, 'fridge'],
        ['Ginger', 100, 'g', 12, null, 'fridge'], ['Garlic', 100, 'g', null, null, 'pantry'],
        ['Chicken', 500, 'g', 1, null, 'fridge'], ['Paneer', 200, 'g', 3, null, 'fridge'],
        ['Curd', 500, 'g', 2, null, 'fridge'], ['Milk', 1, 'L', 2, 0.5, 'fridge'],
        ['Butter', 100, 'g', 30, null, 'fridge'], ['Ghee', 200, 'ml', null, null, 'pantry'],
        ['Curry Leaves', 20, 'g', 4, null, 'fridge'], ['Coriander Leaves', 30, 'g', 1, null, 'fridge'],
        ['Salt', 1, 'kg', null, 0.2, 'pantry'], ['Sugar', 1, 'kg', null, null, 'pantry'],
        ['Turmeric Powder', 20, 'tsp', null, null, 'pantry'], ['Red Chilli Powder', 20, 'tsp', null, null, 'pantry'],
        ['Coriander Powder', 20, 'tsp', null, null, 'pantry'], ['Garam Masala', 10, 'tsp', null, null, 'pantry'],
        ['Cumin Seeds', 20, 'tsp', null, null, 'pantry'], ['Mustard Seeds', 20, 'tsp', null, null, 'pantry'],
        ['Cooking Oil', 1, 'L', null, 0.5, 'pantry'], ['Tea Powder', 250, 'g', null, null, 'pantry'],
        ['Bread', 1, 'packet', -1, null, 'pantry'],
    ];

    /** [days from today, meal type, recipe, servings] */
    private const PLAN = [
        [0, 'breakfast', 'Upma', 3], [0, 'lunch', 'Tomato Rice', 3], [0, 'dinner', 'Chicken Curry', 4], [0, 'dinner', 'Chapati', 4],
        [1, 'breakfast', 'Omelette', 2], [1, 'lunch', 'Curd Rice', 2], [1, 'snack', 'Masala Chai', 2], [1, 'dinner', 'Paneer Butter Masala', 3],
        [2, 'breakfast', 'Ven Pongal', 3], [2, 'lunch', 'Dal', 4], [2, 'dinner', 'Egg Fried Rice', 2],
        [3, 'lunch', 'Lemon Rice', 2], [3, 'dinner', 'Chicken Fried Rice', 2],
    ];

    public function run(): void
    {
        $user = User::where('email', self::EMAIL)->first() ?? User::create([
            'household_id' => Household::create(['name' => 'Demo Kitchen'])->id,
            'name' => 'Demo',
            'email' => self::EMAIL,
            'password' => 'password',
        ]);
        $household = $user->household_id;
        $today = Carbon::today();

        if (! PantryItem::where('household_id', $household)->exists()) {
            $ingredients = Ingredient::all()->keyBy('name');
            foreach (self::PANTRY as [$name, $qty, $unit, $expiresIn, $min, $location]) {
                PantryLedger::receive($household, $ingredients[$name], $qty, Unit::from($unit), TransactionType::ADD, $user->id, [
                    'expiry_date' => $expiresIn === null ? null : $today->copy()->addDays($expiresIn)->toDateString(),
                    'minimum_stock' => $min,
                    'storage_location' => $location,
                ]);
            }
        }

        if (! MealPlan::where('household_id', $household)->exists()) {
            $recipes = Recipe::whereNull('household_id')->pluck('id', 'name');
            foreach (self::PLAN as [$day, $meal, $recipe, $servings]) {
                MealPlan::create([
                    'household_id' => $household, 'date' => $today->copy()->addDays($day), 'meal_type' => $meal,
                    'recipe_id' => $recipes[$recipe], 'servings' => $servings,
                ]);
            }
        }
    }
}
