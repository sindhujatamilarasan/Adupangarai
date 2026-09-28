<?php

namespace App\Models;

use App\Support\Unit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecipeIngredient extends Model
{
    public $timestamps = false;

    protected $fillable = ['recipe_id', 'ingredient_id', 'quantity', 'unit', 'optional'];

    protected function casts(): array
    {
        return ['quantity' => 'float', 'unit' => Unit::class, 'optional' => 'boolean'];
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }
}
