<?php

namespace App\Models;

use App\Support\Unit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Ingredient extends Model
{
    protected $fillable = ['name', 'ingredient_category_id', 'default_unit'];

    protected $hidden = ['normalized_name'];

    protected function casts(): array
    {
        return ['default_unit' => Unit::class];
    }

    protected static function booted(): void
    {
        static::saving(fn (Ingredient $i) => $i->normalized_name = self::normalize($i->name));
    }

    /** "  Red  Onions " and "red onion" map to the same key. */
    public static function normalize(string $name): string
    {
        $words = preg_split('/\s+/', trim(Str::lower($name)), -1, PREG_SPLIT_NO_EMPTY);
        $words[] = Str::singular(array_pop($words) ?? '');

        return implode(' ', $words);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(IngredientCategory::class, 'ingredient_category_id');
    }

    /** Recipes and pantry must use a unit convertible to the default, so they can be compared. */
    public function acceptsUnit(Unit $unit): bool
    {
        return $this->default_unit->isCompatibleWith($unit);
    }
}
