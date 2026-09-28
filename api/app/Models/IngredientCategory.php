<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IngredientCategory extends Model
{
    protected $fillable = ['name', 'sort_order'];

    public function ingredients(): HasMany
    {
        return $this->hasMany(Ingredient::class);
    }
}
