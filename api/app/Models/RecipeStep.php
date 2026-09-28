<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecipeStep extends Model
{
    public $timestamps = false;

    protected $fillable = ['recipe_id', 'position', 'text'];
}
