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

        $item = PantryLedger::receive(
            $user->household_id,
            Ingredient::findOrFail($data['ingredient_id']),
            $data['quantity'],
            Unit::from($data['unit']),
            TransactionType::ADD,
            $user->id,
            $data,
        );

        return response()->json(['data' => $item->fresh('ingredient.category')], $item->wasRecentlyCreated ? 201 : 200);
    }

    /** Add several items at once (e.g. a confirmed voice entry). All or nothing. */
    public function bulk(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(['ADD', 'PURCHASE'])],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.ingredient_id' => ['required', 'distinct', 'exists:ingredients,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0', 'max:1000000'],
            'items.*.unit' => ['required', Rule::enum(Unit::class)],
            'items.*.expiry_date' => ['nullable', 'date'],
        ]);
        $user = $request->user();
        $ingredients = Ingredient::findMany(array_column($data['items'], 'ingredient_id'))->keyBy('id');

        DB::transaction(function () use ($data, $user, $ingredients) {
            foreach ($data['items'] as $row) {
                PantryLedger::receive(
                    $user->household_id, $ingredients[$row['ingredient_id']], $row['quantity'], Unit::from($row['unit']),
                    TransactionType::from($data['type']), $user->id, ['expiry_date' => $row['expiry_date'] ?? null],
                );
            }
        });

        return response()->json(['message' => count($data['items']).' item(s) added to your kitchen.']);
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

    /** Write off every expired item with stock left, as EXPIRED transactions. */
    public function discardExpired(Request $request): JsonResponse
    {
        $user = $request->user();
        $items = PantryItem::where('household_id', $user->household_id)->view('expired')->where('quantity', '>', 0)->with('ingredient')->get();

        DB::transaction(function () use ($items, $user) {
            foreach ($items as $item) {
                PantryLedger::change($item, -$item->quantity, $item->unit, TransactionType::EXPIRED, $user->id, 'Expired - written off');
            }
        });

        return response()->json(['message' => $items->count().' expired item(s) written off.', 'count' => $items->count()]);
    }

    public function destroy(PantryItem $item): JsonResponse
    {
        $this->ensureOwned($item);
        $item->delete();

        return response()->json(['message' => 'Removed from pantry.']);
    }
}
