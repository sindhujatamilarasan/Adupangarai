<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeightLog extends Model
{
    protected $fillable = ['user_id', 'date', 'weight_kg'];

    protected $visible = ['date', 'weight_kg'];

    protected function casts(): array
    {
        return ['date' => 'date:Y-m-d', 'weight_kg' => 'float'];
    }
}
