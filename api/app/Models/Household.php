<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Household extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'weekly_budget'];

    protected function casts(): array
    {
        return ['weekly_budget' => 'integer'];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
