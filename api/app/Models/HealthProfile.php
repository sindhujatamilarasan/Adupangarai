<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HealthProfile extends Model
{
    protected $fillable = ['user_id', 'sex', 'birth_year', 'height_cm', 'start_weight_kg', 'target_weight_kg', 'activity', 'goal', 'pace_kg', 'step_goal', 'started_on'];

    protected $hidden = ['id', 'user_id', 'created_at', 'updated_at'];

    protected function casts(): array
    {
        return [
            'birth_year' => 'integer', 'height_cm' => 'float', 'start_weight_kg' => 'float', 'target_weight_kg' => 'float',
            'pace_kg' => 'float', 'step_goal' => 'integer', 'started_on' => 'date:Y-m-d',
        ];
    }
}
