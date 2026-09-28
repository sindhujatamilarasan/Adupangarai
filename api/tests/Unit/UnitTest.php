<?php

namespace Tests\Unit;

use App\Support\Unit;
use PHPUnit\Framework\TestCase;

class UnitTest extends TestCase
{
    public function test_grams_and_kilograms_convert_both_ways(): void
    {
        $this->assertSame(1.0, Unit::convert(1000, Unit::G, Unit::KG));
        $this->assertSame(1000.0, Unit::convert(1, Unit::KG, Unit::G));
        $this->assertSame(0.25, Unit::convert(250, Unit::G, Unit::KG));
    }

    public function test_millilitres_and_litres_convert_both_ways(): void
    {
        $this->assertSame(1.0, Unit::convert(1000, Unit::ML, Unit::L));
        $this->assertSame(1500.0, Unit::convert(1.5, Unit::L, Unit::ML));
    }

    public function test_kitchen_volume_units_convert_to_ml(): void
    {
        $this->assertSame(240.0, Unit::convert(1, Unit::CUP, Unit::ML));
        $this->assertSame(3.0, Unit::convert(1, Unit::TBSP, Unit::TSP));
    }

    public function test_incompatible_units_do_not_convert(): void
    {
        $this->assertNull(Unit::convert(1, Unit::G, Unit::ML));
        $this->assertNull(Unit::convert(1, Unit::PIECE, Unit::G));
        $this->assertNull(Unit::convert(1, Unit::PACKET, Unit::PIECE));
        $this->assertNull(Unit::convert(1, Unit::CUP, Unit::KG));
    }

    public function test_same_unit_is_identity(): void
    {
        $this->assertSame(6.0, Unit::convert(6, Unit::PIECE, Unit::PIECE));
    }
}
