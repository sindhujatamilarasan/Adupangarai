<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MealPlan extends Model
{
    /** Planner slots: the recipe meal types plus a kids' lunch box packed for school. */
    public const SLOTS = ['breakfast', 'lunch', 'lunchbox', 'snack', 'dinner'];

    protected $fillable = ['household_id', 'date', 'meal_type', 'recipe_id', 'servings', 'cooked_at'];

    protected function casts(): array
    {
        return ['date' => 'date:Y-m-d', 'servings' => 'integer', 'cooked_at' => 'datetime'];
    }

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }
}
