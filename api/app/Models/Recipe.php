<?php

namespace App\Models;

use App\Support\Nutrition;
use App\Support\Quantities;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Recipe extends Model
{
    public const MEAL_TYPES = ['breakfast', 'lunch', 'snack', 'dinner'];

    protected $fillable = [
        'household_id', 'name', 'description', 'meal_type', 'cuisine', 'servings', 'prep_time', 'cook_time', 'is_veg',
        'calories', 'protein_g', 'carbs_g', 'fat_g', 'fiber_g', 'nutrition_estimated_at', 'image_path',
    ];

    protected $hidden = ['image_path'];

    protected $appends = ['total_time', 'is_editable', 'health_tags', 'image_url'];

    protected function casts(): array
    {
        return [
            'is_veg' => 'boolean', 'servings' => 'integer', 'prep_time' => 'integer', 'cook_time' => 'integer',
            'calories' => 'float', 'protein_g' => 'float', 'carbs_g' => 'float', 'fat_g' => 'float', 'fiber_g' => 'float',
            'nutrition_estimated_at' => 'datetime',
        ];
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

    public function getHealthTagsAttribute(): array
    {
        return Nutrition::tags($this);
    }

    /** Relative URL so it works behind the web dev proxy and any host. */
    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? '/storage/'.$this->image_path : null;
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
