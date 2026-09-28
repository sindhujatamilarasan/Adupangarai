<?php

namespace App\Support;

use App\Models\PantryItem;
use App\Models\PantryTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The only place pantry quantities change. Every change is converted into the
 * item's unit, locked, checked against going negative and recorded as a transaction.
 */
class PantryLedger
{
    /** Apply a signed change (positive adds, negative removes). */
    public static function change(
        PantryItem $item,
        float $delta,
        Unit $unit,
        TransactionType $type,
        ?int $userId = null,
        ?string $note = null,
    ): PantryTransaction {
        $converted = Unit::convert($delta, $unit, $item->unit);

        if ($converted === null) {
            throw ValidationException::withMessages([
                'unit' => "Cannot use {$unit->value} for {$item->ingredient->name}; it is stored in {$item->unit->value}.",
            ]);
        }

        return DB::transaction(function () use ($item, $converted, $type, $userId, $note) {
            $locked = PantryItem::lockForUpdate()->findOrFail($item->id);
            $newQuantity = round((float) $locked->quantity + $converted, 3);

            if ($newQuantity < 0) {
                throw ValidationException::withMessages([
                    'quantity' => "Not enough {$item->ingredient->name}: only {$locked->quantity} {$locked->unit->value} in pantry.",
                ]);
            }

            $locked->update(['quantity' => $newQuantity]);
            $item->setRawAttributes($locked->getAttributes(), true);

            return PantryTransaction::create([
                'household_id' => $locked->household_id,
                'pantry_item_id' => $locked->id,
                'ingredient_id' => $locked->ingredient_id,
                'user_id' => $userId,
                'type' => $type,
                'quantity_change' => $converted,
                'unit' => $locked->unit,
                'balance_after' => $newQuantity,
                'note' => $note,
            ]);
        });
    }

    /** Set the counted amount (stock-take); records the difference as ADJUSTMENT. */
    public static function setQuantity(PantryItem $item, float $quantity, Unit $unit, ?int $userId = null, ?string $note = null): PantryTransaction
    {
        return DB::transaction(function () use ($item, $quantity, $unit, $userId, $note) {
            $current = (float) PantryItem::lockForUpdate()->findOrFail($item->id)->quantity;
            $target = Unit::convert($quantity, $unit, $item->unit) ?? throw ValidationException::withMessages([
                'unit' => "Cannot use {$unit->value} for {$item->ingredient->name}; it is stored in {$item->unit->value}.",
            ]);

            return self::change($item, $target - $current, $item->unit, TransactionType::ADJUSTMENT, $userId, $note);
        });
    }
}
