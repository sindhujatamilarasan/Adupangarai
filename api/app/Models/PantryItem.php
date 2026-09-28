<?php

namespace App\Models;

use App\Support\ExpiryStatus;
use App\Support\Unit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class PantryItem extends Model
{
    public const LOCATIONS = ['pantry', 'fridge', 'freezer'];

    public const VIEWS = ['all', 'low_stock', 'expiring_soon', 'expired'];

    protected $fillable = [
        'household_id', 'ingredient_id', 'quantity', 'unit', 'expiry_date', 'minimum_stock', 'storage_location',
    ];

    protected $appends = ['expiry_status', 'days_to_expiry', 'is_low_stock'];

    protected function casts(): array
    {
        return [
            'quantity' => 'float',
            'minimum_stock' => 'float',
            'unit' => Unit::class,
            'expiry_date' => 'date:Y-m-d',
        ];
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PantryTransaction::class);
    }

    public function getExpiryStatusAttribute(): ?string
    {
        return ExpiryStatus::for($this->expiry_date);
    }

    public function getDaysToExpiryAttribute(): ?int
    {
        return $this->expiry_date ? ExpiryStatus::daysLeft($this->expiry_date) : null;
    }

    public function getIsLowStockAttribute(): bool
    {
        return $this->minimum_stock !== null && $this->quantity <= $this->minimum_stock;
    }

    /** SQL mirror of the accessors above so views can be filtered and counted in the database. */
    public function scopeView(Builder $query, string $view): Builder
    {
        $today = Carbon::today();

        return match ($view) {
            'low_stock' => $query->whereNotNull('minimum_stock')->whereColumn('quantity', '<=', 'minimum_stock'),
            'expiring_soon' => $query->whereBetween('expiry_date', [$today, $today->copy()->addDays(ExpiryStatus::SOON_DAYS)]),
            'expired' => $query->where('expiry_date', '<', $today),
            default => $query,
        };
    }
}
