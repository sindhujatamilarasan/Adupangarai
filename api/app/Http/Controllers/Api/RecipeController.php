<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use App\Models\MealPlan;
use App\Models\Recipe;
use App\Support\CookDeduction;
use App\Support\Nutrition;
use App\Support\RecipeMatcher;
use App\Support\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RecipeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'meal_type' => ['nullable', Rule::in(Recipe::MEAL_TYPES)],
            'veg' => ['nullable', 'boolean'],
            'max_time' => ['nullable', 'integer', 'min:1'],
            'mine' => ['nullable', 'boolean'],
            'health' => ['nullable', Rule::in(Nutrition::TAGS)],
        ]);

        $recipes = Recipe::visibleTo($request->user()->household_id)
            ->withCount('ingredients')
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where('name', 'ilike', '%'.addcslashes($s, '%_\\').'%'))
            ->when($filters['meal_type'] ?? null, fn ($q, $t) => $q->where('meal_type', $t))
            ->when(isset($filters['veg']), fn ($q) => $q->where('is_veg', $request->boolean('veg')))
            ->when($filters['max_time'] ?? null, fn ($q, $t) => $q->whereRaw('prep_time + cook_time <= ?', [$t]))
            ->when($request->boolean('mine'), fn ($q) => $q->whereNotNull('household_id'))
            ->when($filters['health'] ?? null, fn ($q, $tag) => Nutrition::whereTag($q, $tag))
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $recipes]);
    }

    public function show(Request $request, Recipe $recipe): JsonResponse
    {
        $this->ensureVisible($recipe);
        $servings = (int) ($request->validate(['servings' => ['nullable', 'integer', 'min:1', 'max:100']])['servings'] ?? $recipe->servings);

        $recipe->load('ingredients.ingredient.category', 'steps');

        return response()->json([
            'data' => [
                ...$recipe->only(['id', 'name', 'description', 'meal_type', 'cuisine', 'servings', 'prep_time', 'cook_time', 'is_veg', 'total_time', 'is_editable', 'health_tags', 'image_url', ...Nutrition::FIELDS]),
                'requested_servings' => $servings,
                'ingredients' => $recipe->scaledIngredients($servings),
                'steps' => $recipe->steps->pluck('text'),
                'match' => RecipeMatcher::match($recipe, RecipeMatcher::pantryFor($request->user()->household_id), $servings),
            ],
        ]);
    }

    public function uploadPhoto(Request $request, Recipe $recipe): JsonResponse
    {
        $this->ensureOwned($recipe);
        $request->validate(['photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120']]);

        $old = $recipe->image_path;
        $recipe->update(['image_path' => $request->file('photo')->store('recipes', 'public')]);
        if ($old) {
            Storage::disk('public')->delete($old);
        }

        return response()->json(['data' => ['image_url' => $recipe->image_url]]);
    }

    public function deletePhoto(Recipe $recipe): JsonResponse
    {
        $this->ensureOwned($recipe);
        if ($recipe->image_path) {
            Storage::disk('public')->delete($recipe->image_path);
            $recipe->update(['image_path' => null]);
        }

        return response()->json(['data' => ['image_url' => null]]);
    }

    /** AI estimate of per-serving nutrition for the household's own recipe. */
    public function estimateNutrition(Recipe $recipe): JsonResponse
    {
        $this->ensureOwned($recipe);
        $recipe->update([...Nutrition::estimate($recipe), 'nutrition_estimated_at' => now()]);

        return response()->json(['data' => $recipe->only([...Nutrition::FIELDS, 'health_tags'])]);
    }

    /** Preview of what cooking would deduct. Changes nothing. */
    public function cookPreview(Request $request, Recipe $recipe): JsonResponse
    {
        $this->ensureVisible($recipe);
        $servings = (int) ($request->validate(['servings' => ['nullable', 'integer', 'min:1', 'max:100']])['servings'] ?? $recipe->servings);
        $recipe->load('ingredients.ingredient');

        return response()->json([
            'data' => CookDeduction::preview($recipe, RecipeMatcher::pantryFor($request->user()->household_id), $servings),
            'servings' => $servings,
        ]);
    }

    public function cook(Request $request, Recipe $recipe): JsonResponse
    {
        $this->ensureVisible($recipe);
        $user = $request->user();
        $data = $request->validate([
            'servings' => ['required', 'integer', 'min:1', 'max:100'],
            'meal_plan_id' => ['nullable', 'integer'],
        ]);

        $plan = null;
        if (isset($data['meal_plan_id'])) {
            $plan = MealPlan::where('household_id', $user->household_id)->where('recipe_id', $recipe->id)->findOrFail($data['meal_plan_id']);
        }

        $rows = CookDeduction::apply($recipe->load('ingredients.ingredient'), $user->household_id, $data['servings'], $user->id, $plan);

        return response()->json(['data' => $rows, 'message' => "Enjoy your {$recipe->name}! Kitchen updated."]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);

        $recipe = DB::transaction(function () use ($data, $request) {
            $recipe = Recipe::create([
                ...$data,
                'household_id' => $request->user()->household_id,
                'nutrition_estimated_at' => isset($data['calories']) ? now() : null,
            ]);
            $this->syncChildren($recipe, $data);

            return $recipe;
        });

        return response()->json(['data' => ['id' => $recipe->id]], 201);
    }

    public function update(Request $request, Recipe $recipe): JsonResponse
    {
        $this->ensureOwned($recipe);
        $data = $this->validated($request);

        DB::transaction(function () use ($recipe, $data) {
            // Ingredients may have changed, so an old estimate no longer applies unless a new one is sent.
            $recipe->update([...array_fill_keys(Nutrition::FIELDS, null), 'nutrition_estimated_at' => null, ...$data]);
            $this->syncChildren($recipe, $data);
        });

        return response()->json(['data' => ['id' => $recipe->id]]);
    }

    public function destroy(Recipe $recipe): JsonResponse
    {
        $this->ensureOwned($recipe);
        if ($recipe->image_path) {
            Storage::disk('public')->delete($recipe->image_path);
        }
        $recipe->delete();

        return response()->json(['message' => 'Recipe deleted.']);
    }

    private function ensureVisible(Recipe $recipe): void
    {
        if ($recipe->household_id !== null) {
            $this->ensureOwned($recipe);
        }
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'meal_type' => ['required', Rule::in(Recipe::MEAL_TYPES)],
            'cuisine' => ['nullable', 'string', 'max:50'],
            'servings' => ['required', 'integer', 'min:1', 'max:100'],
            'prep_time' => ['required', 'integer', 'min:0', 'max:1440'],
            'cook_time' => ['required', 'integer', 'min:0', 'max:1440'],
            'is_veg' => ['required', 'boolean'],
            'ingredients' => ['required', 'array', 'min:1', 'max:50'],
            'ingredients.*.ingredient_id' => ['required', 'distinct', 'exists:ingredients,id'],
            'ingredients.*.quantity' => ['required', 'numeric', 'gt:0', 'max:100000'],
            'ingredients.*.unit' => ['required', Rule::enum(Unit::class)],
            'ingredients.*.optional' => ['boolean'],
            'steps' => ['array', 'max:50'],
            'steps.*' => ['required', 'string', 'max:1000'],
            // Optional estimate (e.g. from a voice draft); all five or none.
            'calories' => ['nullable', 'required_with:protein_g,carbs_g,fat_g,fiber_g', 'numeric', 'min:0', 'max:3000'],
            'protein_g' => ['nullable', 'required_with:calories', 'numeric', 'min:0', 'max:200'],
            'carbs_g' => ['nullable', 'required_with:calories', 'numeric', 'min:0', 'max:400'],
            'fat_g' => ['nullable', 'required_with:calories', 'numeric', 'min:0', 'max:250'],
            'fiber_g' => ['nullable', 'required_with:calories', 'numeric', 'min:0', 'max:100'],
        ]);

        $ingredients = Ingredient::findMany(array_column($data['ingredients'], 'ingredient_id'))->keyBy('id');
        foreach ($data['ingredients'] as $i => $row) {
            $ingredient = $ingredients[$row['ingredient_id']];
            if (! $ingredient->acceptsUnit(Unit::from($row['unit']))) {
                throw ValidationException::withMessages([
                    "ingredients.$i.unit" => "{$ingredient->name} is measured in {$ingredient->default_unit->value}; {$row['unit']} can't be converted.",
                ]);
            }
        }

        return $data;
    }

    private function syncChildren(Recipe $recipe, array $data): void
    {
        $recipe->ingredients()->delete();
        $recipe->ingredients()->createMany(array_map(fn ($row) => [
            'ingredient_id' => $row['ingredient_id'],
            'quantity' => $row['quantity'],
            'unit' => $row['unit'],
            'optional' => $row['optional'] ?? false,
        ], $data['ingredients']));

        $recipe->steps()->delete();
        $recipe->steps()->createMany(array_map(
            fn ($text, $i) => ['position' => $i + 1, 'text' => trim($text)],
            array_values($data['steps'] ?? []),
            array_keys(array_values($data['steps'] ?? [])),
        ));
    }
}
