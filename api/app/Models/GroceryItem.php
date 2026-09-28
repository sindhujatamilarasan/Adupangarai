<?php

namespace App\Models;

use App\Support\Unit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroceryItem extends Model
{
    protected $fillable = [
        'grocery_list_id', 'ingredient_id', 'name', 'category', 'quantity', 'unit', 'source',
        'purchased', 'actual_quantity', 'price', 'added_to_pantry_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'float',
            'actual_quantity' => 'float',
            'price' => 'float',
            'unit' => Unit::class,
            'purchased' => 'boolean',
            'added_to_pantry_at' => 'datetime',
        ];
    }

    public function list(): BelongsTo
    {
        return $this->belongsTo(GroceryList::class, 'grocery_list_id');
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }

    /** What actually came home: the actual quantity if entered, else the listed one. */
    public function boughtQuantity(): ?float
    {
        return $this->actual_quantity ?? $this->quantity;
    }
}
