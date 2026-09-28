<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GroceryItem;
use App\Models\GroceryList;
use App\Models\Ingredient;
use App\Models\MealPlan;
use App\Support\GroceryCalculator;
use App\Support\PantryLedger;
use App\Support\RecipeMatcher;
use App\Support\TransactionType;
use App\Support\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class GroceryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return $this->listResponse(GroceryList::for($request->user()->household_id));
    }

    /** Rebuild the plan-based part of the list for meals between start and end (default: next 7 days). */
    public function generate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'start' => ['nullable', 'date_format:Y-m-d'],
            'end' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start'],
        ]);
        $household = $request->user()->household_id;
        $start = Carbon::parse($data['start'] ?? Carbon::today());
        $end = Carbon::parse($data['end'] ?? $start->copy()->addDays(6));
        $list = GroceryList::for($household);

        DB::transaction(function () use ($list, $household, $start, $end) {
            $list->items()->where('source', 'plan')->where('purchased', false)->delete();

            $plans = MealPlan::where('household_id', $household)
                ->whereBetween('date', [$start, $end])
                ->whereNull('cooked_at')
                ->with('recipe.ingredients.ingredient.category')
                ->get();

            $onList = $list->items()->whereNotNull('ingredient_id')->whereNull('added_to_pantry_at')->get();
            $toBuy = GroceryCalculator::shortfall(
                GroceryCalculator::requirements($plans),
                RecipeMatcher::pantryFor($household),
                $onList,
            );

            foreach ($toBuy as $row) {
                $list->items()->create([
                    'ingredient_id' => $row['ingredient']->id,
                    'name' => $row['ingredient']->name,
                    'category' => $row['ingredient']->category->name,
                    'quantity' => $row['quantity'],
                    'unit' => $row['unit'],
                    'source' => 'plan',
                ]);
            }

            $list->update(['planned_from' => $start, 'planned_to' => $end]);
        });

        return $this->listResponse($list->fresh());
    }

    public function storeItem(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ingredient_id' => ['nullable', 'exists:ingredients,id'],
            'name' => ['required_without:ingredient_id', 'nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:50'],
            'quantity' => ['nullable', 'numeric', 'gt:0', 'max:100000'],
            'unit' => ['nullable', 'required_with:quantity', Rule::enum(Unit::class)],
        ]);

        // Typed names that match a known ingredient ("milk") are linked so they can go to the pantry later.
        $ingredient = isset($data['ingredient_id'])
            ? Ingredient::with('category')->find($data['ingredient_id'])
            : Ingredient::with('category')->where('normalized_name', Ingredient::normalize($data['name']))->first();
        if ($ingredient && isset($data['unit']) && ! $ingredient->acceptsUnit(Unit::from($data['unit']))) {
            throw ValidationException::withMessages(['unit' => "{$ingredient->name} is measured in {$ingredient->default_unit->value}."]);
        }

        $item = GroceryList::for($request->user()->household_id)->items()->create([
            'ingredient_id' => $ingredient?->id,
            'name' => $ingredient?->name ?? trim($data['name']),
            'category' => $ingredient?->category->name ?? ($data['category'] ?? 'Household'),
            'quantity' => $data['quantity'] ?? null,
            'unit' => $data['unit'] ?? $ingredient?->default_unit,
            'source' => 'manual',
        ]);

        return response()->json(['data' => $item], 201);
    }

    public function updateItem(Request $request, GroceryItem $item): JsonResponse
    {
        $this->ensureItemOwned($item);

        $data = $request->validate([
            'purchased' => ['sometimes', 'boolean'],
            'quantity' => ['sometimes', 'nullable', 'numeric', 'gt:0', 'max:100000'],
            'actual_quantity' => ['sometimes', 'nullable', 'numeric', 'gt:0', 'max:100000'],
            'price' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:1000000'],
        ]);

        if ($item->added_to_pantry_at && array_diff(array_keys($data), ['price'])) {
            throw ValidationException::withMessages(['purchased' => 'Already added to the pantry; only the price can change.']);
        }

        $item->update($data);

        return response()->json(['data' => $item]);
    }

    public function destroyItem(GroceryItem $item): JsonResponse
    {
        $this->ensureItemOwned($item);
        $item->delete();

        return response()->json(['message' => 'Removed.']);
    }

    /** Remove bought items that are finished with: non-food, or food already in the pantry. */
    public function clearPurchased(Request $request): JsonResponse
    {
        $list = GroceryList::for($request->user()->household_id);
        $list->items()->where('purchased', true)
            ->where(fn ($q) => $q->whereNull('ingredient_id')->orWhereNotNull('added_to_pantry_at'))
            ->delete();

        return $this->listResponse($list);
    }

    /** Called only after the user confirms. All-or-nothing. */
    public function addToPantry(Request $request): JsonResponse
    {
        $data = $request->validate(['item_ids' => ['required', 'array', 'min:1'], 'item_ids.*' => ['integer', 'distinct']]);
        $user = $request->user();
        $list = GroceryList::for($user->household_id);

        $items = $list->items()->whereIn('id', $data['item_ids'])->with('ingredient')->get();
        if ($items->count() !== count($data['item_ids'])) {
            abort(404);
        }

        foreach ($items as $item) {
            if (! $item->purchased || ! $item->ingredient_id || $item->added_to_pantry_at || ! $item->unit || ! $item->boughtQuantity()) {
                throw ValidationException::withMessages(['item_ids' => "{$item->name} can't be added to the pantry (not a purchased food item with a quantity, or already added)."]);
            }
        }

        DB::transaction(function () use ($items, $user) {
            foreach ($items as $item) {
                PantryLedger::receive(
                    $user->household_id, $item->ingredient, $item->boughtQuantity(), $item->unit,
                    TransactionType::PURCHASE, $user->id, note: 'Grocery purchase',
                );
                $item->update(['added_to_pantry_at' => now()]);
            }
        });

        return $this->listResponse($list);
    }

    private function ensureItemOwned(GroceryItem $item): void
    {
        abort_unless((int) $item->list->household_id === (int) request()->user()->household_id, 404);
    }

    private function listResponse(GroceryList $list): JsonResponse
    {
        $items = $list->items()->orderBy('category')->orderBy('name')->get();

        return response()->json([
            'data' => $items,
            'list' => $list->only(['planned_from', 'planned_to']),
            'summary' => [
                'total' => $items->count(),
                'remaining' => $items->where('purchased', false)->count(),
                'to_add_to_pantry' => $items->filter(fn ($i) => $i->purchased && $i->ingredient_id && ! $i->added_to_pantry_at)->count(),
                'spent' => round($items->where('purchased', true)->sum('price'), 2),
            ],
        ]);
    }
}
