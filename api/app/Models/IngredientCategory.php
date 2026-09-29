<?php

namespace App\Models;

use App\Models\Concerns\HasTamilName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IngredientCategory extends Model
{
    use HasTamilName;

    protected $fillable = ['name', 'name_ta', 'sort_order', 'icon'];

    public function ingredients(): HasMany
    {
        return $this->hasMany(Ingredient::class);
    }
}
