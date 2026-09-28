<?php

namespace App\Support;

/**
 * Supported units and the only place unit conversion happens.
 * Units convert only within the same dimension; anything else returns null.
 */
enum Unit: string
{
    case G = 'g';
    case KG = 'kg';
    case ML = 'ml';
    case L = 'L';
    case CUP = 'cup';
    case TBSP = 'tbsp';
    case TSP = 'tsp';
    case PIECE = 'piece';
    case PACKET = 'packet';

    public function dimension(): string
    {
        return match ($this) {
            self::G, self::KG => 'mass',
            self::ML, self::L, self::CUP, self::TBSP, self::TSP => 'volume',
            self::PIECE => 'piece',
            self::PACKET => 'packet',
        };
    }

    /** Multiplier to the dimension's base unit (g, ml, piece, packet). */
    public function toBase(): float
    {
        return match ($this) {
            self::G, self::ML, self::PIECE, self::PACKET => 1,
            self::KG, self::L => 1000,
            self::CUP => 240,
            self::TBSP => 15,
            self::TSP => 5,
        };
    }

    public function baseUnit(): self
    {
        return match ($this->dimension()) {
            'mass' => self::G,
            'volume' => self::ML,
            'piece' => self::PIECE,
            'packet' => self::PACKET,
        };
    }

    public function isCompatibleWith(self $other): bool
    {
        return $this->dimension() === $other->dimension();
    }

    public static function convert(float $quantity, self $from, self $to): ?float
    {
        if (! $from->isCompatibleWith($to)) {
            return null;
        }

        return round($quantity * $from->toBase() / $to->toBase(), 4);
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /** Spoken/typed unit words -> unit and multiplier ("dozen" = 12 pieces). Null if unknown. */
    public static function fromLoose(?string $word): ?array
    {
        $w = strtolower(trim((string) $word, " .\t\n"));
        $aliases = [
            'g' => 'g', 'gm' => 'g', 'gms' => 'g', 'gram' => 'g', 'grams' => 'g', 'gramme' => 'g', 'grammes' => 'g',
            'kg' => 'kg', 'kgs' => 'kg', 'kilo' => 'kg', 'kilos' => 'kg', 'kilogram' => 'kg', 'kilograms' => 'kg',
            'ml' => 'ml', 'millilitre' => 'ml', 'millilitres' => 'ml', 'milliliter' => 'ml', 'milliliters' => 'ml',
            'l' => 'L', 'ltr' => 'L', 'ltrs' => 'L', 'litre' => 'L', 'litres' => 'L', 'liter' => 'L', 'liters' => 'L',
            'cup' => 'cup', 'cups' => 'cup', 'tbsp' => 'tbsp', 'tablespoon' => 'tbsp', 'tablespoons' => 'tbsp',
            'tsp' => 'tsp', 'teaspoon' => 'tsp', 'teaspoons' => 'tsp',
            'piece' => 'piece', 'pieces' => 'piece', 'pc' => 'piece', 'pcs' => 'piece', 'nos' => 'piece', 'no' => 'piece', '' => 'piece',
            'packet' => 'packet', 'packets' => 'packet', 'pack' => 'packet', 'packs' => 'packet', 'pkt' => 'packet',
        ];

        if (in_array($w, ['dozen', 'dozens'], true)) {
            return [self::PIECE, 12];
        }

        return isset($aliases[$w]) ? [self::from($aliases[$w]), 1] : null;
    }
}
