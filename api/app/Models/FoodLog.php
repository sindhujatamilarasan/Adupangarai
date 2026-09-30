<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FoodLog extends Model
{
    protected $fillable = ['user_id', 'date', 'meal_plan_id', 'name', 'portion', 'calories', 'protein_g', 'source'];

    protected $visible = ['id', 'date', 'meal_plan_id', 'name', 'portion', 'calories', 'protein_g', 'source'];

    protected function casts(): array
    {
        return ['date' => 'date:Y-m-d', 'meal_plan_id' => 'integer', 'portion' => 'float', 'calories' => 'float', 'protein_g' => 'float'];
    }
}
