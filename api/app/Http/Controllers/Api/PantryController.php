<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use App\Models\PantryItem;
use App\Support\PantryLedger;
use App\Support\TransactionType;
use App\Support\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PantryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate(['view' => ['nullable', Rule::in(PantryItem::VIEWS)]]);
        $household = $request->user()->household_id;
        $base = fn () => PantryItem::where('household_id', $household);

        $items = $base()->view($request->input('view', 'all'))
            ->with('ingredient.category')
            ->join('ingredients', 'ingredients.id', '=', 'pantry_items.ingredient_id')
            ->orderBy('ingredients.name')
            ->select('pantry_items.*')
            ->get();

        $counts = collect(PantryItem::VIEWS)->mapWithKeys(fn ($v) => [$v => $base()->view($v)->count()]);

        return response()->json(['data' => $items, 'counts' => $counts]);
    }

    /** Adds stock. If the ingredient is already in the pantry, the quantity is merged into it. */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ingredient_id' => ['required', 'exists:ingredients,id'],
            'quantity' => ['required', 'numeric', 'min:0', 'max:1000000'],
            'unit' => ['required', Rule::enum(Unit::class)],
            'expiry_date' => ['nullable', 'date'],
            'minimum_stock' => ['nullable', 'numeric', 'min:0'],
            'storage_location' => ['nullable', Rule::in(PantryItem::LOCATIONS)],
        ]);
        $user = $request->user();

        $item = DB::transaction(function () use ($data, $user) {
            $item = PantryItem::firstOrCreate(
                ['household_id' => $user->household_id, 'ingredient_id' => $data['ingredient_id']],
                ['quantity' => 0, 'unit' => $data['unit']],
            );

            if (! $item->unit->isCompatibleWith(Unit::from($data['unit']))) {
                throw ValidationException::withMessages([
                    'unit' => "This item is stored in {$item->unit->value}; {$data['unit']} can't be converted.",
                ]);
            }

            $item->fill(collect($data)->only(['minimum_stock', 'storage_location'])->filter(fn ($v) => $v !== null)->all());
            // Keep the earliest expiry so the pantry warns about the oldest stock first.
            if (! empty($data['expiry_date']) && (! $item->expiry_date || $item->expiry_date->gt($data['expiry_date']))) {
                $item->expiry_date = $data['expiry_date'];
            }
            $item->save();

            if ($data['quantity'] > 0) {
                PantryLedger::change($item, $data['quantity'], Unit::from($data['unit']), TransactionType::ADD, $user->id);
            }

            return $item;
        });

        return response()->json(['data' => $item->fresh('ingredient.category')], $item->wasRecentlyCreated ? 201 : 200);
    }

    public function show(Request $request, PantryItem $item): JsonResponse
    {
        $this->ensureOwned($item);

        return response()->json([
            'data' => $item->load('ingredient.category'),
            'transactions' => $item->transactions()->with('user:id,name')->latest('id')->limit(50)->get(),
        ]);
    }

    /** Edits details only; quantity changes go through adjust(). */
    public function update(Request $request, PantryItem $item): JsonResponse
    {
        $this->ensureOwned($item);

        $item->update($request->validate([
            'expiry_date' => ['nullable', 'date'],
            'minimum_stock' => ['nullable', 'numeric', 'min:0'],
            'storage_location' => ['nullable', Rule::in(PantryItem::LOCATIONS)],
        ]));

        return response()->json(['data' => $item->fresh('ingredient.category')]);
    }

    /**
     * ADD adds stock; EXPIRED / DISCARDED remove it; ADJUSTMENT sets the counted amount.
     * PURCHASE and COOKED come only from the shopping and cooking flows.
     */
    public function adjust(Request $request, PantryItem $item): JsonResponse
    {
        $this->ensureOwned($item);

        $data = $request->validate([
            'type' => ['required', Rule::in(['ADD', 'EXPIRED', 'DISCARDED', 'ADJUSTMENT'])],
            'quantity' => ['required', 'numeric', 'min:0', 'max:1000000'],
            'unit' => ['nullable', Rule::enum(Unit::class)],
            'note' => ['nullable', 'string', 'max:255'],
        ]);
        $unit = isset($data['unit']) ? Unit::from($data['unit']) : $item->unit;
        $type = TransactionType::from($data['type']);
        $userId = $request->user()->id;
        $note = $data['note'] ?? null;

        $transaction = match ($type) {
            TransactionType::ADJUSTMENT => PantryLedger::setQuantity($item, $data['quantity'], $unit, $userId, $note),
            TransactionType::ADD => PantryLedger::change($item, $data['quantity'], $unit, $type, $userId, $note),
            default => PantryLedger::change($item, -$data['quantity'], $unit, $type, $userId, $note),
        };

        return response()->json(['data' => $item->fresh('ingredient.category'), 'transaction' => $transaction]);
    }

    public function destroy(PantryItem $item): JsonResponse
    {
        $this->ensureOwned($item);
        $item->delete();

        return response()->json(['message' => 'Removed from pantry.']);
    }
}
