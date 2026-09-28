<?php

namespace App\Models;

use App\Support\Quantities;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Recipe extends Model
{
    public const MEAL_TYPES = ['breakfast', 'lunch', 'snack', 'dinner'];

    protected $fillable = [
        'household_id', 'name', 'description', 'meal_type', 'cuisine', 'servings', 'prep_time', 'cook_time', 'is_veg',
    ];

    protected $appends = ['total_time', 'is_editable'];

    protected function casts(): array
    {
        return ['is_veg' => 'boolean', 'servings' => 'integer', 'prep_time' => 'integer', 'cook_time' => 'integer'];
    }

    public function ingredients(): HasMany
    {
        return $this->hasMany(RecipeIngredient::class);
    }

    public function steps(): HasMany
    {
        return $this->hasMany(RecipeStep::class)->orderBy('position');
    }

    /** Built-in recipes plus the household's own. */
    public function scopeVisibleTo(Builder $query, int $householdId): Builder
    {
        return $query->where(fn ($q) => $q->whereNull('household_id')->orWhere('household_id', $householdId));
    }

    public function getTotalTimeAttribute(): int
    {
        return $this->prep_time + $this->cook_time;
    }

    public function getIsEditableAttribute(): bool
    {
        return $this->household_id !== null;
    }

    /** Ingredients with quantities scaled to $servings (requires ingredients.ingredient loaded). */
    public function scaledIngredients(int $servings): array
    {
        return $this->ingredients->map(fn (RecipeIngredient $ri) => [
            'ingredient_id' => $ri->ingredient_id,
            'ingredient' => $ri->ingredient,
            'quantity' => Quantities::scale($ri->quantity, $this->servings, $servings),
            'unit' => $ri->unit,
            'optional' => $ri->optional,
        ])->all();
    }
}
