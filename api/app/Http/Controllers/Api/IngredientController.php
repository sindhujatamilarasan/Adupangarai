<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use App\Models\IngredientCategory;
use App\Support\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class IngredientController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'integer'],
        ]);

        $ingredients = Ingredient::with('category')
            ->when($request->search, fn ($q, $s) => $q->where('normalized_name', 'like', '%'.Ingredient::normalize($s).'%'))
            ->when($request->category, fn ($q, $c) => $q->where('ingredient_category_id', $c))
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $ingredients]);
    }

    /** Returns the existing ingredient (200) if the normalized name already exists. */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'ingredient_category_id' => ['required', 'exists:ingredient_categories,id'],
            'default_unit' => ['required', Rule::enum(Unit::class)],
        ]);

        $ingredient = Ingredient::firstOrCreate(
            ['normalized_name' => Ingredient::normalize($data['name'])],
            ['name' => trim($data['name']), ...$data],
        );

        return response()->json(['data' => $ingredient->load('category')], $ingredient->wasRecentlyCreated ? 201 : 200);
    }

    public function categories(): JsonResponse
    {
        return response()->json(['data' => IngredientCategory::orderBy('sort_order')->orderBy('name')->get()]);
    }

    public function units(): JsonResponse
    {
        return response()->json([
            'data' => array_map(fn (Unit $u) => ['value' => $u->value, 'dimension' => $u->dimension()], Unit::cases()),
        ]);
    }
}
