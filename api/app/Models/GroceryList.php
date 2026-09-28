<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GroceryList extends Model
{
    protected $fillable = ['household_id', 'planned_from', 'planned_to'];

    protected function casts(): array
    {
        return ['planned_from' => 'date:Y-m-d', 'planned_to' => 'date:Y-m-d'];
    }

    public function items(): HasMany
    {
        return $this->hasMany(GroceryItem::class);
    }

    public static function for(int $householdId): self
    {
        return self::firstOrCreate(['household_id' => $householdId]);
    }
}
