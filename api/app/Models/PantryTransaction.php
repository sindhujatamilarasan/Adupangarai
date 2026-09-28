<?php

namespace App\Models;

use App\Support\TransactionType;
use App\Support\Unit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PantryTransaction extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'household_id', 'pantry_item_id', 'ingredient_id', 'user_id', 'type', 'quantity_change', 'unit', 'balance_after', 'note',
    ];

    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'unit' => Unit::class,
            'quantity_change' => 'float',
            'balance_after' => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
