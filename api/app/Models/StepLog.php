<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StepLog extends Model
{
    protected $fillable = ['user_id', 'date', 'steps'];

    protected function casts(): array
    {
        return ['date' => 'date:Y-m-d', 'steps' => 'integer'];
    }
}
